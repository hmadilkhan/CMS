<div>
    <style>
        .notes-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .note-card {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border-left: 4px solid #2c3e50;
        }

        .note-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateX(3px);
        }

        .note-textarea {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .note-textarea:focus {
            border-color: #2c3e50;
            box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.15);
        }

        .note-header {
            background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .note-actions {
            display: flex;
            gap: 0.5rem;
        }

        .note-icon {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .note-icon:hover {
            transform: scale(1.1);
        }

        .note-icon.edit {
            background: #fff3cd;
            color: #856404;
        }

        .note-icon.delete {
            background: #f8d7da;
            color: #721c24;
        }

        .note-meta {
            font-size: 0.85rem;
            color: #6c757d;
            padding: 0.5rem 0;
            border-top: 1px solid #e9ecef;
            margin-top: 0.5rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .suggestions-dropdown {
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border: none;
        }

        .suggestions-dropdown .list-group-item {
            border: none;
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
        }

        .suggestions-dropdown .list-group-item:hover {
            background: #f8f9fa;
        }

        .suggestions-dropdown .list-group-item.active {
            background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
        }
    </style>
    @can('Notes Section')
        <div class="col-sm-12 mb-3">
            @php
                $showEditFields =
                    ($ghost == 'ghost' && $departmentId == 7) ||
                    ($ghost != 'ghost' && $departmentId == $projectDepartmentId);
            @endphp
            @if ($showEditFields && $viewSource != 'website')
                <div class="notes-section">
                    <div class="note-header">
                        <i class="icofont-ui-note me-2"></i>Add New Notes
                    </div>
                    <div x-data="mentionHandler()" x-init="init()" class="position-relative">
                        <!-- VISIBLE textarea -->
                        <textarea class="form-control note-textarea bg-white" x-model="note" x-ref="noteInput" x-on:input="checkForMention"
                            @keydown.enter="handleEnter($event)" rows="4" :disabled="status === 'loading'"
                            placeholder="Type your note here... Use @ to mention someone"></textarea>

                        <div class="mt-2 text-end">
                            <template x-if="status === 'loading'">
                                <span class="status-badge bg-primary text-white"><i
                                        class="icofont-spinner icofont-spin me-1"></i>Saving...</span>
                            </template>
                            <template x-if="status === 'saved'">
                                <span class="status-badge bg-success text-white"><i
                                        class="icofont-check me-1"></i>Saved!</span>
                            </template>
                            <template x-if="status === 'error'">
                                <span class="status-badge bg-danger text-white"><i class="icofont-close me-1"></i>Error
                                    saving note</span>
                            </template>
                            <template x-if="status === ''">
                                <small class="text-muted"><i class="icofont-keyboard me-1"></i>Press Enter to Save |
                                    Shift+Enter for new line</small>
                            </template>
                        </div>


                        <!-- HIDDEN input sent to Livewire -->
                        <input type="hidden" x-model="rawNote">

                        <!-- Hidden input to store raw version with ID -->
                        <input type="hidden" x-ref="rawNoteInput" wire:model="departmentNote">

                        <!-- SUGGESTIONS dropdown -->
                        <ul x-show="showSuggestions" class="list-group position-absolute bg-white z-10 suggestions-dropdown"
                            style="top: 100%; left: 0; right: 0; max-height: 200px; overflow-y: auto; margin-top: 0.5rem;">
                            <template x-for="(employee, index) in filteredEmployees" :key="employee.id">
                                <li class="list-group-item list-group-item-action"
                                    :class="index === selectedIndex ? 'active text-white' : 'text-black'"
                                    x-text="employee.name" @click="selectEmployee(employee)">
                                </li>
                            </template>
                        </ul>

                        @error('departmentNote')
                            <div class="alert alert-danger mt-2 mb-0"><i class="icofont-warning me-2"></i>{{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>
            @endif

            <div class="note-header mt-4">
                <i class="icofont-listing-box me-2"></i>Department Notes
            </div>
            @foreach ($notes as $value)
                @if ($value->notes != '')
                    <div>
                        @if ($editingNoteId == $value->id)
                            <!-- EDIT MODE -->
                            <div class="note-card" x-data="editMentionHandler()" x-init="initEdit({{ json_encode($value->notes) }})"
                                class="position-relative">
                                <textarea class="form-control note-textarea bg-white" x-model="note" x-ref="noteInput" x-on:input="checkForMention"
                                    @keydown.enter="handleEnter($event)" rows="4" :disabled="status === 'loading'"></textarea>

                                <div class="mt-2 text-end">
                                    <template x-if="status === 'loading'">
                                        <span class="status-badge bg-primary text-white"><i
                                                class="icofont-spinner icofont-spin me-1"></i>Updating...</span>
                                    </template>
                                    <template x-if="status === 'saved'">
                                        <span class="status-badge bg-success text-white"><i
                                                class="icofont-check me-1"></i>Updated!</span>
                                    </template>
                                    <template x-if="status === 'error'">
                                        <span class="status-badge bg-danger text-white"><i
                                                class="icofont-close me-1"></i>Error updating</span>
                                    </template>
                                    <template x-if="status === ''">
                                        <small class="text-muted"><i class="icofont-keyboard me-1"></i>Press Enter to Update
                                            | Shift+Enter for new line</small>
                                    </template>
                                </div>

                                <!-- HIDDEN input sent to Livewire -->
                                <input type="hidden" x-model="rawNote">

                                <!-- Hidden input to store raw version with ID -->
                                <input type="hidden" x-ref="rawNoteInput" wire:model="departmentNote">

                                <!-- SUGGESTIONS dropdown -->
                                <ul x-show="showSuggestions"
                                    class="list-group position-absolute bg-white z-10 suggestions-dropdown"
                                    style="top: 100%; left: 0; right: 0; max-height: 200px; overflow-y: auto; margin-top: 0.5rem;">
                                    <template x-for="(employee, index) in filteredEmployees" :key="employee.id">
                                        <li class="list-group-item list-group-item-action"
                                            :class="index === selectedIndex ? 'active text-white' : 'text-black'"
                                            x-text="employee.name" @click="selectEmployee(employee)">
                                        </li>
                                    </template>
                                </ul>

                                <div class="mt-3 text-end">
                                    <button type="button" class="btn btn-dark btn-sm" wire:click="cancelEdit">
                                        <i class="icofont-close me-1"></i>Cancel
                                    </button>
                                </div>

                                @error('departmentNote')
                                    <div class="alert alert-danger mt-2 mb-0"><i
                                            class="icofont-warning me-2"></i>{{ $message }}</div>
                                @enderror
                            </div>
                        @else
                            <!-- VIEW MODE -->
                            <div class="note-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <textarea class="form-control note-textarea" disabled
                                            style="resize: none; background-color: white; border: none; padding: 0; min-height: 60px; height: auto; overflow: hidden;"
                                            onload="this.style.height = this.scrollHeight + 'px'">{{ $value->notes }}</textarea>
                                    </div>
                                    @if ($value->user_id == auth()->user()->id && $viewSource != 'website')
                                        <div class="note-actions ms-3">
                                            <div class="note-icon edit" wire:click="editNote({{ $value->id }})">
                                                <i class="icofont-pencil"></i>
                                            </div>
                                            <div class="note-icon delete" wire:click="deleteNote({{ $value->id }})"
                                                wire:confirm="Are you sure that you want to delete ?">
                                                <i class="icofont-trash"></i>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="note-meta d-flex justify-content-between align-items-center">
                                    <span><i class="icofont-user me-1"></i>{{ !empty($value->user) ? $value->user->name : '' }}</span>
                                    <span><i class="icofont-clock-time me-1"></i>{{ !empty($value->created_at) ? date('m/d/Y H:i:s', strtotime($value->created_at)) : '' }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    @endcan
</div>
<script>
    // Auto-resize textareas to fit content
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('textarea.note-textarea[disabled]').forEach(function(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        });
    });

    function mentionHandler() {
        return {
            employees: {!! json_encode($employees) !!},
            filteredEmployees: [],
            selectedIndex: 0,
            showSuggestions: false,
            note: '', // shown to user
            rawNote: '', // sent to Livewire
            mentions: [], // track mentions with their IDs
            status: '', // '', 'loading', 'saved'

            init() {
                this.filteredEmployees = this.employees;
            },

            checkForMention(event) {

                const cursorPos = this.$refs.noteInput.selectionStart;
                const value = this.note;
                const atPosition = value.lastIndexOf('@', cursorPos);
                if (atPosition !== -1) {
                    const query = value.substring(atPosition + 1, cursorPos).toLowerCase();
                    this.filteredEmployees = this.employees.filter(emp =>
                        emp.name.toLowerCase().includes(query)
                    );
                    this.showSuggestions = this.filteredEmployees.length > 0;
                } else {
                    this.showSuggestions = false;
                }

                this.syncRawNote();
            },

            selectEmployee(employee) {
                const textarea = this.$refs.noteInput;
                const value = textarea.value;
                const cursorPos = textarea.selectionStart;
                const atPosition = value.lastIndexOf('@', cursorPos);
                const before = value.substring(0, atPosition);
                const after = value.substring(cursorPos);

                // Text shown to user (just the name)
                const mentionText = `@${employee.name}`;

                // Add to mentions array for tracking
                this.mentions.push({
                    name: employee.name,
                    id: employee.id,
                    text: mentionText
                });

                // Update visible textarea (user sees only names)
                const newValue = `${before}${mentionText} ${after}`;
                textarea.value = newValue;

                // Update the Alpine.js model
                this.note = newValue;

                // Update cursor position after the mention
                const newCursorPos = atPosition + mentionText.length + 1;
                textarea.setSelectionRange(newCursorPos, newCursorPos);

                // Wait for Alpine.js to process the change, then sync
                this.$nextTick(() => {
                    this.syncRawNote();
                });

                this.showSuggestions = false;
                this.selectedIndex = 0;
            },

            syncRawNote() {

                let temp = this.note;

                // Replace each mention with its ID format
                this.mentions.forEach(mention => {
                    const regex = new RegExp(`@${mention.name}\\b`, 'g');
                    temp = temp.replace(regex, `@${mention.id}:${mention.name}`);
                });

                this.rawNote = temp;
                this.$refs.rawNoteInput.value = temp;
                this.$refs.rawNoteInput.dispatchEvent(new Event('input'));
            },

            async handleEnter(event) {
                if (event.shiftKey) {
                    // Allow Shift+Enter to create new line
                    return;
                }

                // Prevent default Enter behavior (save instead)
                event.preventDefault();

                this.syncRawNote();
                this.status = 'loading';
                // ✅ Wait a tick to sync to Livewire
                await this.$nextTick();

                // ✅ Ensure Livewire has the updated value
                this.$wire.departmentNote = this.rawNote;

                // ✅ Now call Livewire save
                this.$wire.save().then(() => {
                    this.note = '';
                    this.rawNote = '';
                    this.mentions = []; // Clear mentions
                    this.status = 'saved';

                    setTimeout(() => {
                        this.status = '';
                    }, 2000);
                }).catch(() => {
                    this.status = 'error';
                });;

                // Clear the textarea after save
                this.note = '';
            }
        };
    }

    function editMentionHandler() {
        return {
            employees: {!! json_encode($employees) !!},
            filteredEmployees: [],
            selectedIndex: 0,
            showSuggestions: false,
            note: '', // shown to user
            rawNote: '', // sent to Livewire
            mentions: [], // track mentions with their IDs
            status: '', // '', 'loading', 'saved'

            initEdit(initialNote) {
                this.filteredEmployees = this.employees;
                this.note = initialNote;
                this.rawNote = initialNote;

                // Parse existing mentions from the initial note
                this.parseExistingMentions(initialNote);
            },

            parseExistingMentions(noteText) {
                // Extract mentions in format @id:name from the initial note
                const mentionRegex = /@(\d+):([^@\s]+)/g;
                let match;
                this.mentions = [];

                while ((match = mentionRegex.exec(noteText)) !== null) {
                    this.mentions.push({
                        id: match[1],
                        name: match[2],
                        text: `@${match[2]}`
                    });
                }

                // Convert the note to show only names for the user
                this.note = noteText.replace(/@(\d+):([^@\s]+)/g, '@$2');
            },

            checkForMention(event) {
                const cursorPos = this.$refs.noteInput.selectionStart;
                const value = this.note;
                const atPosition = value.lastIndexOf('@', cursorPos);
                if (atPosition !== -1) {
                    const query = value.substring(atPosition + 1, cursorPos).toLowerCase();
                    this.filteredEmployees = this.employees.filter(emp =>
                        emp.name.toLowerCase().includes(query)
                    );
                    this.showSuggestions = this.filteredEmployees.length > 0;
                } else {
                    this.showSuggestions = false;
                }

                this.syncRawNote();
            },

            selectEmployee(employee) {
                const textarea = this.$refs.noteInput;
                const value = textarea.value;
                const cursorPos = textarea.selectionStart;
                const atPosition = value.lastIndexOf('@', cursorPos);
                const before = value.substring(0, atPosition);
                const after = value.substring(cursorPos);

                // Text shown to user (just the name)
                const mentionText = `@${employee.name}`;

                // Add to mentions array for tracking
                this.mentions.push({
                    name: employee.name,
                    id: employee.id,
                    text: mentionText
                });

                // Update visible textarea (user sees only names)
                const newValue = `${before}${mentionText} ${after}`;
                textarea.value = newValue;

                // Update the Alpine.js model
                this.note = newValue;

                // Update cursor position after the mention
                const newCursorPos = atPosition + mentionText.length + 1;
                textarea.setSelectionRange(newCursorPos, newCursorPos);

                // Wait for Alpine.js to process the change, then sync
                this.$nextTick(() => {
                    this.syncRawNote();
                });

                this.showSuggestions = false;
                this.selectedIndex = 0;
            },

            syncRawNote() {
                let temp = this.note;

                // Replace each mention with its ID format
                this.mentions.forEach(mention => {
                    const regex = new RegExp(`@${mention.name}\\b`, 'g');
                    temp = temp.replace(regex, `@${mention.id}:${mention.name}`);
                });

                this.rawNote = temp;
                this.$refs.rawNoteInput.value = temp;
                this.$refs.rawNoteInput.dispatchEvent(new Event('input'));
            },

            async handleEnter(event) {
                if (event.shiftKey) {
                    // Allow Shift+Enter to create new line
                    return;
                }

                // Prevent default Enter behavior (save instead)
                event.preventDefault();

                this.syncRawNote();
                this.status = 'loading';
                // ✅ Wait a tick to sync to Livewire
                await this.$nextTick();

                // ✅ Ensure Livewire has the updated value
                this.$wire.departmentNote = this.rawNote;

                // ✅ Now call Livewire updateNote
                this.$wire.updateNote().then(() => {
                    this.status = 'saved';

                    setTimeout(() => {
                        this.status = '';
                    }, 2000);
                }).catch(() => {
                    this.status = 'error';
                });
            }
        };
    }
</script>
