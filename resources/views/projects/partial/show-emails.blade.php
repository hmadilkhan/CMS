@foreach ($departments as $department)
<div class="col-md-12">
    <div class="card border-0 mb-4 no-bg">
        <div style="background-color: #E5E4E2;" class="card-header py-3 px-0 d-sm-flex align-items-center   justify-content-between border-bottom border-top">
            <h3 class=" fw-bold flex-fill mb-0 mt-sm-0 px-2">
                {{ $department->name }}
            </h3>
        </div>
    </div>
</div>
@php
$emails = $project->emails
->filter(function ($item) use ($department) {
return $item->department_id == $department->id;
})
->values();
@endphp
@if (!empty($emails))
<div class="row clearfix">
    <div class="col-lg-12">
        <div class="card">
            @foreach ($emails as $key => $email)
            <div class="chat">
                <div class="chat-history">
                    <ul class="m-b-0">
                        @if ($email->message_id == '')
                        <li class="clearfix">
                            @if(!empty($email->user))
                            <div class="message-data">
                                <span class="message-data-time float-right">Sent By {{$email->user->name}}</span>
                            </div>
                            </br>
                            @endif
                            <div class="message other-message float-right  mt-3">
                                <p class="text-start"> {{ $email->subject }}</p></br>
                                <div class="text-start">{!! $email->body !!}</div>
                                @if(!empty($email->attachments))
                                @foreach($email->attachments as $attachment)
                                <a href="{{asset('/storage/emails/'.$attachment->file)}}"><span class="badge bg-primary">{{$attachment->file}}</span></a>
                                @endforeach
                                @endif
                            </div>
                        </li>
                        @else
                        <li class="clearfix">
                            <div class="message-data">
                                <span class="message-data-time">10:12 AM, Today</span>
                            </div>
                            <div class="message my-message">
                                {{ $email->subject }}</br>{{ $email->body }}
                                @if(!empty($email->attachments))
                                @foreach($email->attachments as $attachment)
                                <a href="{{asset('/storage/emails/'.$attachment->file)}}"><span class="badge bg-primary">{{$attachment->file}}</span></a>
                                @endforeach
                                @endif
                            </div>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif
@endforeach