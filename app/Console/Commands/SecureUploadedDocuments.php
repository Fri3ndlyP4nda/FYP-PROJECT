<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Move uploaded documents off the public disk.
 *
 * Uploads are written to the 'private' disk (storage/app/secure) and served
 * through SecureFileController, which re-applies the same ownership rule that
 * guards the page. Documents uploaded before that change went to the 'public'
 * disk instead, which is symlinked from public/storage and therefore served
 * directly by the web server with no authentication at all.
 *
 * That is a live exposure: a payment receipt, a portfolio, or an evidence file
 * containing an applicant's IC number can be fetched by anyone holding or
 * guessing the URL. This command moves them to where the current code already
 * expects to find them, after which the secure route serves them and the public
 * URL 404s.
 *
 * Run --dry-run first. The move is per-file and skips anything already present
 * at the destination, so it is safe to re-run.
 */
class SecureUploadedDocuments extends Command
{
    protected $signature = 'apel:secure-documents
                            {--dry-run : List what would move without touching anything}
                            {--keep-public : Copy rather than move, leaving the exposed original in place}';

    protected $description = 'Move uploaded documents from the public disk to the private one';

    /**
     * Directories the application uploads into. Anything outside these is left
     * alone - the public disk is also where genuinely public assets would live.
     */
    private const UPLOAD_DIRECTORIES = [
        'payment_receipts',
        'apel_c/evidence',
        'apel_c/portfolio',
        'assessment_papers',
        'assessment_answers',

        // The Application model has a supporting_docs field and 13 files were
        // sitting here. No view links them, so nothing in the interface was
        // serving them - but the web server was, to anyone with the URL.
        'supporting_docs',
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $keep = (bool) $this->option('keep-public');

        $public = Storage::disk('public');
        $private = Storage::disk('private');

        $moved = 0;
        $skipped = 0;
        $failed = 0;
        $bytes = 0;

        $this->newLine();
        $this->line($dry
            ? 'Dry run. Nothing will be written.'
            : ($keep ? 'Copying to the private disk; public originals will remain.' : 'Moving to the private disk.'));
        $this->newLine();

        foreach (self::UPLOAD_DIRECTORIES as $directory) {
            $files = $public->files($directory);

            if ($files === []) {
                continue;
            }

            $this->line("<comment>{$directory}</comment> — ".count($files).' file(s)');

            foreach ($files as $path) {
                if ($private->exists($path)) {
                    $skipped++;

                    continue;
                }

                $size = (int) $public->size($path);

                if ($dry) {
                    $this->line(sprintf('    would move  %-58s %8s', $path, self::human($size)));
                    $moved++;
                    $bytes += $size;

                    continue;
                }

                // Read/write rather than move(): the two disks are separate
                // filesystems, and a failed move must not lose the only copy.
                $stream = $public->readStream($path);

                if ($stream === null || $private->writeStream($path, $stream) === false) {
                    $this->error("    FAILED      {$path}");
                    $failed++;

                    continue;
                }

                if (is_resource($stream)) {
                    fclose($stream);
                }

                // Only unlink once the destination is confirmed readable.
                if (! $private->exists($path)) {
                    $this->error("    FAILED      {$path} (not readable after write)");
                    $failed++;

                    continue;
                }

                if (! $keep) {
                    $public->delete($path);
                }

                $moved++;
                $bytes += $size;
            }
        }

        $this->newLine();
        $this->line(sprintf(
            '  %s: %d   already private: %d   failed: %d   %s',
            $dry ? 'Would move' : 'Moved',
            $moved,
            $skipped,
            $failed,
            self::human($bytes),
        ));

        if ($dry && $moved > 0) {
            $this->newLine();
            $this->warn('  These are currently downloadable without signing in.');
            $this->line('  Run without --dry-run to move them behind SecureFileController.');
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private static function human(int $bytes): string
    {
        foreach ([['GB', 1073741824], ['MB', 1048576], ['KB', 1024]] as [$unit, $step]) {
            if ($bytes >= $step) {
                return round($bytes / $step, 1).' '.$unit;
            }
        }

        return $bytes.' B';
    }
}
