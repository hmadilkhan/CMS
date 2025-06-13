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
                {{-- <form wire:submit.prevent="save"> --}}
                <div class="position-relative">
                    <textarea class="form-control bg-white border border-dark" wire:model="departmentNote" wire:keydown.enter="save"
                        rows="3"></textarea>
                    <label class="float-right mb-4 fst-italic">Press Enter to Save</label>
                    {{-- <button type="submit" class="btn btn-primary position-absolute" style="bottom: 10px; right: 10px;">
                            <i class="icofont-save"></i> Save
                        </button> --}}
                    @error('departmentNote')
                        <div class="text-danger message mt-1">{{ $message }}</div>
                    @enderror
                </div>
                {{-- </form> --}}
            @endif
            </br>
            <label for="formFileMultipleoneone" class="form-label fw-bold flex-fill mb-2 mt-sm-0">Department Notes</label>
            @foreach ($notes as $value)
                @if ($value->notes != '')
                    <div>
                        <label class="form-control mt-3" disabled rows="3">{{ $value->notes }}
                            <b><i>{{ !empty($value->user) ? '( Added by ' . $value->user->name . ')' : '' }}</i></b></label>
                        <div class="float-right">
                            <label
                                class="mb-4 fst-italic">{{ !empty($value->user) ? $value->user->name . ' on ' . date('m/d/Y H:i:s', strtotime($value->created_at)) : '' }}</label>
                            <i class="icofont-trash text-danger" style="cursor: pointer;" wire:click="deleteNote"
                                wire:confirm="Are you sure that you want to delete ?"></i>
                        </div>

                        <br />
                    </div>
                @endif
            @endforeach
        </div>
    @endcan
</div>
