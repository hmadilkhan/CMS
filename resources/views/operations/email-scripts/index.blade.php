@extends('layouts.master')
@section('title', 'Email Scripts')
@section('content')
    <link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/42.0.0/ckeditor5.css">
    @if (session('success'))
        <div class="alert alert-primary" role="alert">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">
            {{ session('error') }}
        </div>
    @endif
    @include('operations.partials.index-styles')
    <style>
        .email-script-tabs {
            border-bottom: 1px solid var(--solen-primary-border, #e5e7eb);
            gap: 0.25rem;
            margin-bottom: 1rem;
        }

        .email-script-tabs .nav-link {
            border: 0;
            border-bottom: 2px solid transparent;
            border-radius: 0;
            color: var(--solen-muted, #64748b);
            font-weight: 600;
            padding: 0.75rem 1.15rem;
        }

        .email-script-tabs .nav-link:hover {
            border-bottom-color: var(--solen-primary-border, #e5e7eb);
            color: var(--solen-warm-text, #451a03);
        }

        .email-script-tabs .nav-link.active {
            background: transparent;
            border-bottom-color: var(--solen-primary, #f59e0b);
            color: var(--solen-warm-text, #451a03);
        }

        .email-script-tabs .tab-count {
            display: inline-block;
            min-width: 1.5rem;
            margin-left: 0.4rem;
            padding: 0.05rem 0.4rem;
            border-radius: 999px;
            background: var(--solen-primary-soft, #f1f5f9);
            color: inherit;
            font-size: 0.72rem;
            font-weight: 700;
        }

        /* select2 and CKEditor size themselves on init. When the page opens on
           the Templates tab the script form is still display:none, so pin the
           widths instead of letting them resolve to 0. */
        #emailScriptTabsContent .select2-container {
            width: 100% !important;
        }

        #emailScriptTabsContent .ck-editor {
            width: 100%;
        }

        .template-tag-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
        }

        .template-tag {
            border: 1px solid var(--solen-primary-border, #e5e7eb);
            background: var(--solen-primary-soft, #f8fafc);
            color: var(--solen-warm-text, #451a03);
            border-radius: 999px;
            padding: 0.3rem 0.7rem;
            font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 0.75rem;
            cursor: pointer;
        }

        .template-tag:hover {
            background: var(--solen-cream-strong, #fde68a);
        }

        .template-tag.is-copied {
            background: #16a34a;
            border-color: #16a34a;
            color: #ffffff;
        }
    </style>
    <div class="operation-page-header">
        <div>
            <h1 class="operation-page-title">Email Scripts</h1>
            <p class="operation-page-subtitle">Maintain department-specific scripts for email workflows.</p>
        </div>
        <div class="operation-summary">
            <span>Total Records</span>
            <strong>{{ $emailScripts->count() }}</strong>
        </div>
    </div>

    <ul class="nav nav-tabs email-script-tabs" id="emailScriptTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'scripts' ? 'active' : '' }}" id="tab-scripts-btn"
                data-bs-toggle="tab" data-bs-target="#tab-scripts" type="button" role="tab"
                aria-controls="tab-scripts" aria-selected="{{ $activeTab === 'scripts' ? 'true' : 'false' }}">
                Email Scripts
                <span class="tab-count">{{ $emailScripts->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'templates' ? 'active' : '' }}" id="tab-templates-btn"
                data-bs-toggle="tab" data-bs-target="#tab-templates" type="button" role="tab"
                aria-controls="tab-templates" aria-selected="{{ $activeTab === 'templates' ? 'true' : 'false' }}">
                System Email Templates
                <span class="tab-count">{{ count($notificationTemplates) }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="emailScriptTabsContent">
    <div class="tab-pane fade {{ $activeTab === 'scripts' ? 'show active' : '' }}" id="tab-scripts" role="tabpanel"
        aria-labelledby="tab-scripts-btn">
    <div class="card operation-card">
        <div class="card-header">
            <h4 class="card-title">Create Email Script</h4>
        </div>
        <div class="card-body">
            <form class="operation-form" method="POST"
                action="{{ !empty($script) ? route('email.scripts.update', $script->id) : route('email.scripts.store') }}">
                @csrf
                <input type="hidden" name="id" value="{{ !empty($script) ? $script->id : '' }}" />
                <div class="row g-3">
                    <div class="col-xl-4 col-lg-6 col-md-6 col-12">
                        <label class="form-label">Email Type</label></br>
                        <select class="form-select select2" aria-label="Default select Call" id="email_type_id"
                            name="email_type_id" required>
                            <option value="">Select Email Type</option>
                            @foreach ($emailTypes as $emailType)
                                <option {{ !empty($script) && $script->email_type_id == $emailType->id ? 'selected' : '' }}
                                    value="{{ $emailType->id }}">
                                    {{ $emailType->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('email_type_id')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-xl-4 col-lg-6 col-md-6 col-12">
                        <label class="form-label">Department</label>
                        <select class="form-select select2" aria-label="Default select Call" id="department"
                            name="department" required>
                            <option value="">Select Department</option>
                            @foreach ($departments as $department)
                                <option {{ !empty($script) && $script->department_id == $department->id ? 'selected' : '' }}
                                    value="{{ $department->id }}">
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('department')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-xl-4 col-lg-6 col-md-6 col-12">
                        <label>Extra Filter</label>
                        <input type="text" class="form-control @error('extra') is-invalid @enderror" id="extra"
                            name="extra" placeholder="Enter Extra Filter"
                            value="{{ old('extra', !empty($script) ? $script->extra_filter : '') }}">
                        @error('extra')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-12 col-sm-12">
                        <label class="form-label">Script</label></br>
                        <textarea id="editor" name="script" class="form-control @error('script') is-invalid @enderror" rows="5">{!! old('script', !empty($script) ? $script->script : '') !!}</textarea>
                        @error('script')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <div class="operation-actions">
                            <button type="submit" class="btn btn-primary" value="save"><i
                                    class="icofont-save"></i> Save
                            </button>
                            <a href="{{ route('email.scripts.list') }}" class="btn btn-outline-secondary"><i
                                    class="icofont-ban"></i> Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card operation-card mt-3">
        <div class="card-header">
            <h4 class="card-title">Email Scripts</h4>
        </div>
        <div class="card-body">
            <table id="example1" class="table table-hover operation-table datatable">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Type</th>
                        <th>Department</th>
                        <th>Script</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($emailScripts as $key => $emailScript)
                        <tr>
                            <td>{{ ++$key }}</td>
                            <td>{{ $emailScript->email->name ?? 'N/A' }}</td>
                            <td>{{ $emailScript->department->name ?? "N/A" }}</td>
                            <td>{{ $emailScript->script }}</td>
                            <td class="text-center">
                                <a class="action-link" data-toggle="tooltip" title="Edit"
                                    href="{{ route('email.scripts.list', $emailScript->id) }}">
                                    <i class="icofont-pencil text-warning"></i></a>
                                <a class="action-link ml-2" data-toggle="tooltip" title="Delete"
                                    onclick="deleteDealerModal('{{ $emailScript->id }}')">
                                    <i class="icofont-trash text-danger"></i></a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($emailScripts->isEmpty())
            <div class="empty-state">No email scripts have been added yet.</div>
            @endif
        </div>
    </div>
    </div>

    <div class="tab-pane fade {{ $activeTab === 'templates' ? 'show active' : '' }}" id="tab-templates" role="tabpanel"
        aria-labelledby="tab-templates-btn">
    <div class="card operation-card">
        <div class="card-header">
            <h4 class="card-title">System Email Templates</h4>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">
                These are the emails the CRM sends on its own. Edit the wording here; the
                tags in curly braces are filled in with real data when the email is sent.
            </p>

            @if ($editingTemplate)
                <form class="operation-form" method="POST" action="{{ route('notification.template.update') }}">
                    @csrf
                    <input type="hidden" name="key" value="{{ $editingTemplate['key'] }}" />
                    <div class="row g-3">
                        <div class="col-12">
                            <h5 class="mb-1">{{ $editingTemplate['name'] }}</h5>
                            <p class="text-muted mb-0">{{ $editingTemplate['description'] }}</p>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Subject</label>
                            <input type="text" class="form-control" name="subject" required
                                value="{{ old('subject', $editingTemplate['subject']) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Body</label>
                            <textarea id="templateEditor" name="body" class="form-control" rows="10">{!! old('body', $editingTemplate['body']) !!}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label d-block">Available tags &mdash; click to copy</label>
                            <div class="template-tag-list">
                                @foreach ($editingTemplate['placeholders'] as $tag => $tagDescription)
                                    <button type="button" class="template-tag" data-tag="{{ '{' . $tag . '}' }}"
                                        title="{{ $tagDescription }}">{{ '{' . $tag . '}' }}</button>
                                @endforeach
                            </div>
                            <small class="text-muted d-block mt-2">
                                Paste a tag anywhere in the subject or body. Only the tags above work in this
                                template &mdash; anything else is rejected when you save.
                            </small>
                        </div>
                        <div class="col-12">
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                    {{ old('is_active', $editingTemplate['is_active']) ? 'checked' : '' }}>
                                <span class="form-check-label ms-2">Active (uncheck to fall back to the built-in wording)</span>
                            </label>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="operation-actions">
                                <button type="submit" class="btn btn-primary"><i class="icofont-save"></i> Save Template</button>
                                <a href="{{ route('email.scripts.list') }}" class="btn btn-outline-secondary"><i class="icofont-ban"></i> Cancel</a>
                            </div>
                        </div>
                    </div>
                </form>
                <hr class="my-4">
            @endif

            <table class="table table-hover operation-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Email</th>
                        <th>Sent When</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($notificationTemplates as $templateKey => $template)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $template['name'] }}</strong>
                                <div class="text-muted small">{{ $template['group'] }}</div>
                            </td>
                            <td class="text-muted small">{{ $template['description'] }}</td>
                            <td class="small">{{ $template['subject'] }}</td>
                            <td>
                                @if (!$template['is_active'])
                                    <span class="badge bg-secondary">Inactive</span>
                                @elseif ($template['is_customised'])
                                    <span class="badge bg-success">Edited</span>
                                @else
                                    <span class="badge bg-light text-dark">Default</span>
                                @endif
                            </td>
                            <td class="text-center text-nowrap">
                                <a class="action-link" data-toggle="tooltip" title="Edit"
                                    href="{{ route('email.scripts.list', ['template' => $templateKey]) }}">
                                    <i class="icofont-pencil text-warning"></i></a>
                                @if ($template['is_customised'])
                                    <a class="action-link ml-2" data-toggle="tooltip" title="Reset to default"
                                        onclick="resetTemplate('{{ $templateKey }}')">
                                        <i class="icofont-refresh text-danger"></i></a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    </div>
    </div>

    <form id="resetTemplateForm" method="POST" action="{{ route('notification.template.reset') }}" class="d-none">
        @csrf
        <input type="hidden" name="key" id="resetTemplateKey">
    </form>
    <!-- Modal  Delete Folder/ File-->
    <div class="modal fade" id="deleteproject" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md modal-dialog-scrollable">
            <input type="hidden" id="deleteId" />
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title  fw-bold" id="deleteprojectLabel"> Delete item Permanently?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body justify-content-center flex-column d-flex">
                    <i class="icofont-ui-delete text-danger display-2 text-center mt-2"></i>
                    <p class="mt-4 fs-5 text-center">You can only delete this item Permanently</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger color-fff" onclick="deleteDealerFee()">Delete</button>
                </div>
            </div>
        </div>
    </div>
    <script type="importmap">
    {
                "imports": {
                    "ckeditor5": "https://cdn.ckeditor.com/ckeditor5/42.0.0/ckeditor5.js",
                    "ckeditor5/": "https://cdn.ckeditor.com/ckeditor5/42.0.0/"
                }
            }
        </script>
    <script type="module">
        import {
            ClassicEditor,
            Essentials,
            Paragraph,
            Bold,
            Italic,
            Font
        } from 'ckeditor5';

        const editorConfig = {
                plugins: [Essentials, Paragraph, Bold, Italic, Font],
                toolbar: [
                    'undo', 'redo', '|', 'bold', 'italic', '|',
                    'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor'
                ]
            };

        // Both editors share one config: the script box and, when a system
        // template is open, its body.
        ClassicEditor.create(document.querySelector('#editor'), editorConfig)
            .then(editor => {
                window.editor = editor;
            })
            .catch(error => {
                // console.log(error);
            });

        const templateEditorEl = document.querySelector('#templateEditor');

        if (templateEditorEl) {
            ClassicEditor.create(templateEditorEl, editorConfig)
                .then(editor => {
                    window.templateEditor = editor;
                })
                .catch(error => {
                    // console.log(error);
                });
        }
    </script>
@endsection
@section('scripts')
    <script>
        // The scripts table is a DataTable. When the page opens on the
        // Templates tab it is initialised while hidden, which leaves the column
        // widths wrong until it is measured again.
        document.querySelectorAll('#emailScriptTabs [data-bs-toggle="tab"]').forEach(function(button) {
            button.addEventListener('shown.bs.tab', function() {
                if (window.jQuery && $.fn.dataTable) {
                    $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                }
            });
        });

        function resetTemplate(key) {
            if (!confirm('Reset this template back to its built-in wording?')) {
                return;
            }
            document.getElementById('resetTemplateKey').value = key;
            document.getElementById('resetTemplateForm').submit();
        }

        // Tags are copied rather than inserted: the cursor may be in the
        // subject input or inside CKEditor, and copy works for both.
        document.addEventListener('click', function(event) {
            const tag = event.target.closest('.template-tag');
            if (!tag) {
                return;
            }
            const text = tag.dataset.tag;
            const done = function() {
                tag.classList.add('is-copied');
                setTimeout(function() {
                    tag.classList.remove('is-copied');
                }, 900);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(done).catch(function() {});
                return;
            }
            const helper = document.createElement('textarea');
            helper.value = text;
            document.body.appendChild(helper);
            helper.select();
            try {
                document.execCommand('copy');
                done();
            } catch (e) {}
            document.body.removeChild(helper);
        });

        function deleteDealerModal(id) {
            $("#deleteId").val(id);
            $("#deleteproject").modal("show")
        }

        function deleteDealerFee() {
            $.ajax({
                method: "POST",
                url: "{{ route('email.scripts.delete') }}",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: $("#deleteId").val()
                },
                success: function(response) {
                    if (response.status == 200) {
                        location.reload();
                    }
                }
            });
        }
    </script>
@endsection
