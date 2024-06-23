{!! $callScript->script !!}
{{--<div class="content">
    @if($department == 1 && $callId == 1)
    <label style="font-size: 15px;text-align: justify;text-justify: inter-word;">Hello this is <span style="font-weight:bold">{{auth()->user()->name}}</span> calling from <span style="font-weight:bold">Solen Energy Co.</span> may I speak to <span style="font-weight:bold">{{$project->customer->first_name.' '.$project->customer->last_name}}</span>.</br></br>

        Hello <span style="font-weight:bold">{{$project->customer->first_name.' '.$project->customer->last_name}}</span>, I’m calling from the deal review department to confirm that we have received your
        project in our system. I will be emailing you a review packet that will contain your contract and other
        agreements that you have signed with <span style="font-weight:bold">{{$project->customer->salespartner->name}}</span></br></br>

        Please review all the documents attached in the email and confirm all the information is accurate. Once
        we receive a response email from you, someone from our department will be in contact with you to
        further assist you with the rest of your project.</br></br>

        While I have you on the phone, did you have any questions regarding the recent solar agreement that you
        signed with <span style="font-weight:bold">{{$project->customer->salespartner->name}}</span> ?</br></br>
    </label>

    @elseif($department == 1 && $callId == 2)

    <label style="font-size: 15px;text-align: justify;text-justify: inter-word;">
        Hello this is <span style="font-weight:bold">{{auth()->user()->name}}</span> calling from <span style="font-weight:bold">Solen Energy Co.</span> may I speak to <span style="font-weight:bold">{{$project->customer->first_name.' '.$project->customer->last_name}}</span>.</br></br>
        Hello <span style="font-weight:bold">{{$project->customer->first_name.' '.$project->customer->last_name}}</span>, Thank you for returning the review packet to us in a timely manner. Upon doing a final
        check we were able to determine that your project is ready to be moved to our next department.</br></br>
        Please expect a call from someone in our welcome call department where they will be helping you
        schedule your site assessment. The welcome call team will also help walk you through the process for the
        remainder of your solar project.</br></br>
        Thank you so much for letting me review this information with you, and we look forward to providing you
        an exceptional experience for the coming weeks.</br></br>
        Before I let you go, was there anything else that I am able to help you with?

    </label>
    @elseif($department == 2 && $callId == 1)
    <label style="font-size: 15px;text-align: justify;text-justify: inter-word;">
        Hello this is <span style="font-weight:bold">{{auth()->user()->name}}</span> calling from <span style="font-weight:bold">Solen Energy Co.</span> may I speak to <span style="font-weight:bold">{{$project->customer->first_name.' '.$project->customer->last_name}}</span>.</br></br>
        Hello <span style="font-weight:bold">{{$project->customer->first_name.' '.$project->customer->last_name}}</span>, I want to start off this conversation by thanking you for choosing <span style="font-weight:bold">{{$project->customer->salespartner->name}}</span> as
        your solar provider.</br></br>
        This is your official welcome call and while I have you on the phone I would like to schedule your site
        survey assessment.</br></br>
        To complete the site survey assessment we will need to send a site assessor to your home to take some
        pictures and gather some data so we can create your site plan for your solar system. The assessment
        should take around an hour to complete.</br></br>
        Can you please tell me your next available date when I can send my assessor out to your home?</br></br>

        Perfect, our site assessor will be there __________ between the hours of _____________.
        Once our site assessor has gathered all the information he needs from the assessment you will receive a
        phone call from our department confirming we have received all the data before we move your project to
        our Engineering Department.</br></br>

        Lastly, while I still have you on the phone can you please confirm if your home is a part of any
        HomeOwner Association.<span style="color:red;font-weight:bold;"> Note: If they are a part of an HOA, please input the name and number of the
            HOA.</span>

        Thank you for that information, is there anything else I can assist you with while I have you on the phone?
    </label>
    @elseif($department == 2 && $callId == 2)
    <label style="font-size: 15px;text-align: justify;text-justify: inter-word;">
        Hello this is <span style="font-weight:bold">{{auth()->user()->name}}</span> calling from <span style="font-weight:bold">Solen Energy Co.</span> may I speak to <span style="font-weight:bold">{{$project->customer->first_name.' '.$project->customer->last_name}}</span>.</br></br>
        Hello <span style="font-weight:bold">{{$project->customer->first_name.' '.$project->customer->last_name}}</span> I’m calling in regards to your site survey assessment to let you know we have received
        the photos from our site assessor and have determined that your project is ready to be moved to our next
        department.
        Please expect a call from the Engineering Department in the next few days to let you know they have
        emailed you a preliminary site plan and to go over a few details regarding your project.
        Is there anything else I can assist you with while I have you on the phone?
    </label>
    @elseif($department == 3 && $callId == 1)
    <label style="font-size: 15px;text-align: justify;text-justify: inter-word;">
        Hello this is <span style="font-weight:bold">{{auth()->user()->name}}</span> calling from <span style="font-weight:bold">Solen Energy Co.</span> may I speak to <span style="font-weight:bold">{{$project->customer->first_name.' '.$project->customer->last_name}}</span>.</br></br>
        Hello <span style="font-weight:bold">{{$project->customer->first_name.' '.$project->customer->last_name}}</span> , I’m calling from the engineering department to let you know that your site plan is
        ready for your approval and is waiting for you in your inbox. If you approve of the layout and
        equipment locations, please reply with "Approved." If you have any questions or concerns regarding the
        site plan please let us know within 48 hours. If we do not hear from you within those 48 hours, we will be
        moving your project forward. I'm looking forward to your response.
        </br></br>
        Is there anything else I can assist you with while I have you on the phone?
    </label>
    @elseif($department == 3 && $callId == 2)
    <label style="font-size: 15px;text-align: justify;text-justify: inter-word;">
        Hello this is <span style="font-weight:bold">{{auth()->user()->name}}</span> calling from <span style="font-weight:bold">Solen Energy Co.</span> may I speak to <span style="font-weight:bold">{{$project->customer->first_name.' '.$project->customer->last_name}}</span>.</br></br>
        Hello <span style="font-weight:bold">{{$project->customer->first_name.' '.$project->customer->last_name}}</span> , I’m calling in regards to your finalized plans to let you know we have them in hand and
        now we are moving your project to our next department.
        </br></br>
        Please expect a call from the Permit Department in the next few days to let you know they have received
        your finalized engineered plans and for them to tell you when they will start the permit process for your
        project.</br></br>
        Is there anything else I can assist you with while I have you on the phone?
    </label>
    @endif
</div>--}}