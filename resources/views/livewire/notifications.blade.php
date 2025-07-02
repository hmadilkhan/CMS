<div class="card border-0 w380">
    <div class="card-header border-0 p-3">
        <h5 class="mb-0 font-weight-light d-flex justify-content-between">
            <span>Notifications</span>
            <span class="badge text-white">{{ auth()->user()->unreadNotifications->count() }}</span>
        </h5>
    </div>
    <div class="tab-content card-body">
        <div class="tab-pane fade show active">
            <ul class="list-unstyled list mb-0">
                @foreach ($notifications as $notification)
                @php
                 $data = json_decode($notification->data, true);   
                @endphp
                <li class="py-2 mb-1 border-bottom">
                    <a wire:click.prevent="unread('{{ $notification->id }}','{{$data['url']}}')" href="#" class="d-flex" wire:loading.attr="disabled">
                        <img class="avatar rounded-circle" src="assets/images/xs/avatar1.jpg" alt="">
                        <div class="flex-fill ms-2">
                            <p class="d-flex justify-content-between mb-0 "><span class="font-weight-bold">{{$data['mentioned_by']}}</span> <small>{{ $notification->created_at->diffForHumans() }}</small></p>
                            <span class="">{{$data['note']}} <span class="badge bg-success">{{$data['project_name']}}</span></span>
                        </div>
                    </a>
                </li>
               @endforeach
            </ul>
        </div>
    </div>
    <a class="card-footer text-center border-top-0" href="#"> View all notifications</a>
</div>