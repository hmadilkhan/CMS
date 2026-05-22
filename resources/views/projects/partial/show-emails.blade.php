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
            @php
                $rawBody = (string) $email->body;
                $decodedBody = html_entity_decode(strip_tags($rawBody), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $decodedBody = preg_replace("/\r\n|\r/", "\n", $decodedBody);
                $decodedBody = trim(preg_replace("/\n{3,}/", "\n\n", $decodedBody));
                $quotePattern = '/(\n\s*On\s.+?wrote:\s*|\n\s*-{2,}\s*Original Message\s*-{2,}|\n\s*From:\s.+\n\s*Sent:\s.+)/is';
                $bodyParts = preg_split($quotePattern, $decodedBody, 2, PREG_SPLIT_DELIM_CAPTURE);
                $latestReply = trim($decodedBody);
                $quotedMessage = '';

                if (count($bodyParts) >= 3) {
                    $latestReply = trim($bodyParts[0]);
                    $quotedMessage = trim($bodyParts[1] . $bodyParts[2]);
                }

                $quotedMessageId = 'quoted-email-' . $email->id;
            @endphp
            <div class="chat">
                <div class="chat-history">
                    <ul class="m-b-0">
                        @if ($email->direction === 'sent' || ($email->message_id == '' && !empty($email->user)))
                        <li class="clearfix">
                            @if(!empty($email->user))
                            <div class="message-data">
                                <span class="message-data-time float-right">Sent By {{$email->user->name}} - {{date("d M Y H:i a", strtotime($email->created_at))}}</span>
                            </div>
                            </br>
                            @endif
                            <div class="message other-message float-right  mt-3">
                                <p class="text-start"> {{ $email->subject }}</p></br>
                                <div class="text-start">{!! $email->body !!}</div>
                                @if(!empty($email->attachments))
                                @foreach($email->attachments as $attachment)
                                <a target="_blank" href="{{asset('/storage/emails/'.$attachment->file)}}"><span class="badge bg-primary">{{$attachment->file}}</span></a>
                                @endforeach
                                @endif
                            </div>
                        </li>
                        @else
                        <li class="clearfix">
                            <div class="message-data">
                                <span class="message-data-time">{{date("d M Y H:i a", strtotime($email->received_date))}}</span>
                            </div>
                            <div class="message my-message">
                                <p class="text-start fw-bold mb-2">{{ $email->subject }}</p>
                                <div class="text-start bg-white rounded p-3 border">
                                    {!! nl2br(e($latestReply)) !!}
                                </div>
                                @if($quotedMessage !== '')
                                <button class="btn btn-sm btn-outline-secondary mt-2" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#{{ $quotedMessageId }}"
                                    aria-expanded="false" aria-controls="{{ $quotedMessageId }}">
                                    <i class="icofont-rounded-down me-1"></i>Show original message
                                </button>
                                <div class="collapse mt-2" id="{{ $quotedMessageId }}">
                                    <div class="text-start bg-light rounded p-3 border text-muted small">
                                        {!! nl2br(e($quotedMessage)) !!}
                                    </div>
                                </div>
                                @endif
                                @if(!empty($email->attachments))
                                @foreach($email->attachments as $attachment)
                                <a target="_blank" href="{{asset('/storage/emails/'.$attachment->file)}}"><span class="badge bg-primary">{{$attachment->file}}</span></a>
                                @endforeach
                                @endif
                            </div>
                            <button class="btn btn-warning float-right mt-2" onclick='ReplyEmail(@json($email->subject))'>Reply</button>
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
<script>
    function ReplyEmail(subject) {
        $('#subject').val('RE: ' + subject);
        $('#subject').focus();
    }
</script>
