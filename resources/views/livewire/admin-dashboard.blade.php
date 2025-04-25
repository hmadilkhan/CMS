<div>
    <div class="container-xxl">
        <div class="row g-4">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Dashboard</h5>
                        <form wire:submit.prevent="updateDates" class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <label for="startDate" class="form-label mb-0">From:</label>
                                <input type="date" class="form-control form-control-sm" id="startDate" wire:model="startDate" style="width: 150px;">
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <label for="endDate" class="form-label mb-0">To:</label>
                                <input type="date" class="form-control form-control-sm" id="endDate" wire:model="endDate" style="width: 150px;">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Apply Filter</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-4 mt-2">
             <div class="col-md-4">
                <livewire:new-projects-card :startDate="$startDate" :endDate="$endDate" :key="'new-projects-' . time()" />
            </div>
           <div class="col-md-4">
                <livewire:department-time-chart :startDate="$startDate" :endDate="$endDate" :key="'department-time-' . time()" />
            </div>
            <div class="col-md-4">
                <livewire:installation-chart :startDate="$startDate" :endDate="$endDate" :key="'installation-' . time()" />
            </div>
            <div class="col-md-4">
                <livewire:pto-approval-chart :startDate="$startDate" :endDate="$endDate" :key="'pto-approval-' . time()" />
            </div>
        </div>
    </div>
</div>
