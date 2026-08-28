{{-- The one write the Zones board makes: a zone move, with an optional note.
     Every zone is a valid destination, the archive included.

     Included once per page; the cards that open it are re-rendered by ajax, so
     the click handler is delegated rather than bound to the buttons. On success
     it fires `zone:moved` and lets the host page decide what to refresh. --}}
<div class="modal fade" id="zoneMoveModal" tabindex="-1" aria-labelledby="zoneMoveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="zoneMoveForm">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="zoneMoveModalLabel">Move Zone</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="zoneMoveProjectId" value="">

                    <p class="mb-3">
                        <span class="text-muted d-block small">Project</span>
                        <span class="fw-bold" id="zoneMoveProjectLabel"></span>
                    </p>

                    <div class="mb-3">
                        <label for="zoneMoveZoneId" class="form-label fw-bold">Move to zone</label>
                        <select id="zoneMoveZoneId" class="form-select">
                            @foreach ($movableZones as $zone)
                                <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-1">
                        <label for="zoneMoveNote" class="form-label fw-bold">Note <span
                                class="text-muted fw-normal">(optional)</span></label>
                        <textarea id="zoneMoveNote" class="form-control" rows="3"
                            placeholder="Why is it moving?"></textarea>
                    </div>

                    <div id="zoneMoveError" class="alert alert-danger mt-3 mb-0 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="zoneMoveSubmit">Move Project</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    {{-- Bootstrap's bundle is loaded at the end of the layout, so wait for it. --}}
    document.addEventListener('DOMContentLoaded', function () {
        const modalElement = document.getElementById('zoneMoveModal');

        if (!modalElement) {
            return;
        }

        const modal = new bootstrap.Modal(modalElement);
        const form = document.getElementById('zoneMoveForm');
        const projectIdInput = document.getElementById('zoneMoveProjectId');
        const zoneSelect = document.getElementById('zoneMoveZoneId');
        const noteInput = document.getElementById('zoneMoveNote');
        const projectLabel = document.getElementById('zoneMoveProjectLabel');
        const errorBox = document.getElementById('zoneMoveError');
        const submitButton = document.getElementById('zoneMoveSubmit');

        // Delegated: the board's cards are replaced on every filter change.
        document.addEventListener('click', function (event) {
            const button = event.target.closest('[data-zone-move]');

            if (!button) {
                return;
            }

            event.preventDefault();
            projectIdInput.value = button.dataset.projectId;
            projectLabel.textContent = button.dataset.projectName;
            zoneSelect.value = button.dataset.zoneId;
            noteInput.value = '';
            errorBox.classList.add('d-none');
            errorBox.textContent = '';
            modal.show();
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            errorBox.classList.add('d-none');
            submitButton.disabled = true;

            fetch('{{ route('zones.move') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    project_id: projectIdInput.value,
                    zone_id: zoneSelect.value,
                    note: noteInput.value
                })
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, data: data };
                    });
                })
                .then(function (result) {
                    submitButton.disabled = false;

                    if (!result.ok) {
                        errorBox.textContent = result.data.message || 'The project could not be moved.';
                        errorBox.classList.remove('d-none');

                        return;
                    }

                    modal.hide();
                    document.dispatchEvent(new CustomEvent('zone:moved', { detail: result.data }));
                })
                .catch(function () {
                    submitButton.disabled = false;
                    errorBox.textContent = 'The project could not be moved. Please try again.';
                    errorBox.classList.remove('d-none');
                });
        });
    });
</script>
