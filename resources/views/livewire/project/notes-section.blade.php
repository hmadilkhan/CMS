<div class="col-sm-12 mb-3">
    <label for="formFileMultipleoneone" class="form-label fw-bold flex-fill mb-2 mt-sm-0">Add New Notes</label>
    <form wire:submit.prevent="save">
        <div class="position-relative">
            <textarea class="form-control bg-white border border-dark" wire:model="departmentNote" rows="3"></textarea>
            <button type="submit" class="btn btn-primary position-absolute" style="bottom: 10px; right: 10px;">
                <i class="icofont-save"></i> Save
            </button>
            @error('departmentNote')
                <div class="text-danger message mt-1">{{ $message }}</div>
            @enderror
        </div>
    </form>
    </br>
    <label for="formFileMultipleoneone" class="form-label fw-bold flex-fill mb-2 mt-sm-0">Department Notes</label>
    @foreach ($notes as $value)
        @if ($value->notes != '')
            <label class="form-control" disabled rows="3">{{ $value->notes }}
                <b><i>{{ !empty($value->user) ? '( Added by ' . $value->user->name . ')' : '' }}</i></b></label>
        @endif
    @endforeach
</div>
