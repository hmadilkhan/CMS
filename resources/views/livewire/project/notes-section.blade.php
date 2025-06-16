<div>
    @can('Notes Section')
        <div class="col-sm-12 mb-3">
            @php
                $showEditFields =
                    ($ghost == 'ghost' && $departmentId == 7) ||
                    ($ghost != 'ghost' && $departmentId == $projectDepartmentId);
            @endphp
            @if ($showEditFields)
                <label for="formFileMultipleoneone" class="form-label fw-bold flex-fill mb-2 mt-sm-0">Add New Notes</label>

                <div x-data="mentionHandler()" x-init="init()" class="position-relative">
                    <!-- VISIBLE textarea -->
                    <textarea class="form-control bg-white border border-dark" x-model="note" x-ref="noteInput" x-on:input="checkForMention"
                        x-on:keydown.enter.prevent="handleEnter" rows="3" :disabled="status === 'loading'"></textarea>

                    <div class="mt-1 text-end fst-italic medium">
                        <template x-if="status === 'loading'">
                            <span class="text-primary">Saving...</span>
                        </template>
                        <template x-if="status === 'saved'">
                            <span class="text-success">Saved!</span>
                        </template>
                        <template x-if="status === 'error'">
                            <span class="text-danger">Error saving note.</span>
                        </template>
                        <template x-if="status === ''">
                            <span class="text-dark">Press Enter to Save</span>
                        </template>
                    </div>


                    <!-- HIDDEN input sent to Livewire -->
                    <input type="hidden" x-model="rawNote">

                    <!-- Hidden input to store raw version with ID -->
                    <input type="hidden" x-ref="rawNoteInput" wire:model="departmentNote">

                    <!-- SUGGESTIONS dropdown -->
                    <ul x-show="showSuggestions" class="list-group position-absolute bg-white z-10 shadow rounded border"
                        style="top: 100%; left: 0; right: 0; max-height: 200px; overflow-y: auto;">
                        <template x-for="(employee, index) in filteredEmployees" :key="employee.id">
                            <li class="list-group-item list-group-item-action"
                                :class="index === selectedIndex ? 'active text-white' : 'text-black'" x-text="employee.name"
                                @click="selectEmployee(employee)">
                            </li>
                        </template>
                    </ul>

                    @error('departmentNote')
                        <div class="text-danger message mt-1">{{ $message }}</div>
                    @enderror
                </div>
            @endif
            </br>
            <label for="formFileMultipleoneone" class="form-label fw-bold flex-fill mb-2 mt-sm-0">Department Notes</label>
            @foreach ($notes as $value)
                @if ($value->notes != '')
                    <div>
                        <div class="d-flex justify-content-between  align-items-center">
                            <label class="form-control mt-3" disabled rows="3">{{ $value->notes }}
                                <b><i>{{ !empty($value->user) ? '( Added by ' . $value->user->name . ')' : '' }}</i></b></label>
                            <i class="icofont-trash text-danger mt-2 fs-4 ml-1" style="cursor: pointer;"
                                wire:click="deleteNote({{ $value->id }})"
                                wire:confirm="Are you sure that you want to delete ?"></i>
                        </div>
                        <div class="float-right">
                            <label
                                class="mb-4 fst-italic">{{ !empty($value->user) ? $value->user->name . ' on ' . date('m/d/Y H:i:s', strtotime($value->created_at)) : '' }}</label>
                        </div>
                        <br />
                    </div>
                @endif
            @endforeach
        </div>
    @endcan
</div>
<script>
    function mentionHandler() {
        return {
            employees: {!! json_encode($employees) !!},
            filteredEmployees: [],
            selectedIndex: 0,
            showSuggestions: false,
            note: '', // shown to user
            rawNote: '', // sent to Livewire
            mentions: [],
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
                const atPosition = value.lastIndexOf("@");
                const before = value.substring(0, atPosition);

                // Text shown to user
                const mentionText = `@${employee.name}`;

                // Text sent to backend (stored in another hidden input)
                const mentionTag = `@${employee.id}:${employee.name}`;

                textarea.value = `${before}${mentionText} `;

                // Store raw version for backend (in a hidden input Livewire-bound)
                this.$refs.rawNoteInput.value = `${before}${mentionTag} `;
                this.$refs.rawNoteInput.dispatchEvent(new Event('input'));

                this.showSuggestions = false;
                this.selectedIndex = 0;
            },

            syncRawNote() {
                let temp = this.note;
                this.mentions.forEach(emp => {
                    const regex = new RegExp(`@${emp.name}\\b`, 'g');
                    temp = temp.replace(regex, `@${emp.id}:${emp.name}`);
                });
                this.rawNote = temp;
            },

            async handleEnter() {
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
</script>
