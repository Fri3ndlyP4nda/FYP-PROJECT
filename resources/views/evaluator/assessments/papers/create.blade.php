@extends('layouts.app')

@section('content')
    <style>
        .source-label {
            margin: 0;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            color: #837e75;
            display: inline-flex;
            align-items: center;
            user-select: none;
        }
        .source-label.active {
            background: #ffffff;
            color: #6e1730;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        .source-label:hover:not(.active) {
            color: #6e1730;
            background: rgba(139, 30, 63, 0.04);
        }
    </style>

    <div class="container paper-create-shell">
        <section class="page-hero">
            <div>
                <span class="section-pill">Evaluator Module</span>
                <h2>Upload Assessment Paper</h2>
                <p class="muted page-hero-text">
                    Upload the assessment paper for the selected application and provide the instructions for the student.
                </p>
            </div>

            <div class="hero-actions">
                <a href="{{ route('evaluator.applications.index') }}" class="btn btn-secondary">Back to Applications</a>
                <a href="{{ route('evaluator.assessment.papers.index') }}" class="btn">View Paper Library</a>
            </div>
        </section>

        <div class="paper-create-layout">
            <div class="card form-main-card">
                @if ($errors->any())
                    <div class="alert alert-error">
                        <ul style="padding-left: 18px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('evaluator.assessment.papers.store', $application->_id) }}"
                    enctype="multipart/form-data">
                    @csrf

                    <div class="source-toggle-group" style="display: flex; gap: 10px; margin-bottom: 25px; background: #f2e7ea; padding: 4px; border-radius: 10px; width: fit-content;">
                        <label class="source-label active" id="source-library-label">
                            <input type="radio" name="paper_source" value="library" checked style="display: none;" onchange="toggleSource('library')">
                            <x-field-error name="paper_source" />
                            Choose from Library
                        </label>
                        <label class="source-label" id="source-upload-label">
                            <input type="radio" name="paper_source" value="upload" style="display: none;" onchange="toggleSource('upload')">
                            <x-field-error name="paper_source" />
                            Upload New File
                        </label>
                    </div>

                    {{-- 1. LIBRARY SECTION --}}
                    <div id="section-library">
                        <label for="library_paper_select">Select Paper from Library</label>
                        <select name="library_paper_id" id="library_paper_select" required style="width: 100%; max-width: 100%;">
                            <option value="">-- Select a Paper --</option>
                            @foreach ($libraryPapers as $paper)
                                <option value="{{ $paper->_id }}" data-instructions="{{ $paper->instructions }}" data-file="{{ asset('storage/' . $paper->question_file) }}">
                                    {{ $paper->title }}
                                </option>
                            @endforeach
                        </select>
                        <x-field-error name="library_paper_id" />

                        {{-- Preview Box --}}
                        <div id="paper-preview-box" style="display: none; margin-top: 20px; padding: 18px; background: #faf8f9; border: 1px solid #f0e6e9; border-radius: 12px;">
                            <h4 style="color: #6e1730; margin-bottom: 8px; font-weight: 700;">Paper Preview</h4>
                            <p style="margin-bottom: 8px;"><strong>Title:</strong> <span id="preview-title" style="color: #2e2a2b;"></span></p>
                            <p style="margin-bottom: 12px; line-height: 1.5;"><strong>Instructions:</strong> <span id="preview-instructions" style="white-space: pre-wrap; font-size: 13.5px; color: #555;"></span></p>
                            <a href="#" id="preview-file-link" target="_blank" class="link" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600; font-size: 13.5px;">
                                📄 View PDF File
                            </a>
                        </div>
                    </div>

                    {{-- 2. UPLOAD SECTION --}}
                    <div id="section-upload" style="display: none;">
                        <label for="upload_title">Paper Title</label>
                        <input type="text" name="title" id="upload_title" value="{{ old('title') }}"
                            placeholder="Example: APEL Assessment Paper 1">
                        <x-field-error name="title" />

                        <label for="upload_instructions">Instructions</label>
                        <textarea name="instructions" id="upload_instructions" rows="7"
                            placeholder="Write instructions for the student before they begin the assessment...">{{ old('instructions') }}</textarea>
                        <x-field-error name="instructions" />

                        <label for="upload_file">Question PDF</label>
                        <div class="upload-box">
                            <input type="file" name="question_file" id="upload_file" accept=".pdf,application/pdf">
                            <x-field-error name="question_file" />
                            <p>Only PDF format is allowed for assessment papers.</p>
                            <small>Make sure the uploaded file is the final version before submission.</small>
                        </div>
                    </div>

                    {{-- Global Fields --}}
                    <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #f0e6e9;">
                        <label for="submission_deadline" style="font-weight: 700; color: #6e1730;">Submission Deadline</label>
                        <input type="datetime-local" name="submission_deadline" id="submission_deadline" min="{{ now()->
                        <x-field-error name="submission_deadline" />format('Y-m-d\TH:i') }}" required style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #cfc9be; font-size: 14px; margin-top: 5px;">
                        <p style="margin: 5px 0 0 0; font-size: 12px; color: #837e75;">Specify the date and time by which the student must upload their answer.</p>
                    </div>

                    <div class="form-submit-row" style="margin-top: 25px;">
                        <a href="{{ route('evaluator.applications.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn">Assign Paper</button>
                    </div>
                </form>
            </div>

            <aside class="info-side-card">
                <span class="side-label">Upload Guide</span>
                <h3>Before you upload</h3>

                <ul class="check-list">
                    <li>Make sure the paper title is clear and specific.</li>
                    <li>Provide complete instructions for the student.</li>
                    <li>Upload the correct PDF file for the selected application.</li>
                    <li>Check the document before final submission.</li>
                </ul>

                <div class="tip-box">
                    <strong>Tip</strong>
                    <p>
                        Use a clear naming style for the title and file so the paper is easier to identify later in the
                        paper library.
                    </p>
                </div>
            </aside>
        </div>
    </div>

    <script>
        function toggleSource(source) {
            const libraryLabel = document.getElementById('source-library-label');
            const uploadLabel = document.getElementById('source-upload-label');
            const librarySection = document.getElementById('section-library');
            const uploadSection = document.getElementById('section-upload');
            
            const librarySelect = document.getElementById('library_paper_select');
            const uploadTitle = document.getElementById('upload_title');
            const uploadFile = document.getElementById('upload_file');

            if (source === 'library') {
                libraryLabel.classList.add('active');
                uploadLabel.classList.remove('active');
                librarySection.style.display = 'block';
                uploadSection.style.display = 'none';

                librarySelect.required = true;
                uploadTitle.required = false;
                uploadFile.required = false;
            } else {
                libraryLabel.classList.remove('active');
                uploadLabel.classList.add('active');
                librarySection.style.display = 'none';
                uploadSection.style.display = 'block';

                librarySelect.required = false;
                uploadTitle.required = true;
                uploadFile.required = true;
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const librarySelect = document.getElementById('library_paper_select');
            const previewBox = document.getElementById('paper-preview-box');
            const previewTitle = document.getElementById('preview-title');
            const previewInstructions = document.getElementById('preview-instructions');
            const previewFileLink = document.getElementById('preview-file-link');

            librarySelect.addEventListener('change', function () {
                const selectedOption = this.options[this.selectedIndex];
                if (this.value) {
                    const title = selectedOption.text.trim();
                    const instructions = selectedOption.getAttribute('data-instructions') || 'No instructions provided.';
                    const fileUrl = selectedOption.getAttribute('data-file');

                    previewTitle.textContent = title;
                    previewInstructions.textContent = instructions;
                    previewFileLink.href = fileUrl;

                    previewBox.style.display = 'block';
                } else {
                    previewBox.style.display = 'none';
                }
            });
        });
    </script>
@endsection
