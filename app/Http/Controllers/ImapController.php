<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Webklex\IMAP\Facades\Client;
use Webklex\PHPIMAP\Query\WhereQuery as query;

class ImapController extends Controller
{
    public function fetchEmails()
    {
        $client = Client::account('default');
        $client->connect();
        if ($client->isConnected()) {
            $folders = $client->getFolders();
            foreach ($folders as $key => $folder) {
                // return $folder->search();
                $query = $folder->query();
                $messages = $query->from('aptechadil@gmail.com')->get();
                // $messages = $folder->messages()->all()->get();
                foreach ($messages as $key => $message) {
                    echo $message->message_id . '<br />';
                    echo $message->getSubject() . '<br />';
                    // echo $message->getHTMLBody() . '<br />';
                    echo $message->getTextBody() . '<br />';
                    echo 'Attachments: ' . $message->getAttachments()->count() . '<br />';
                    echo '<br />';
                }
            }
            // $messages = $query->from('example@domain.com')->get();
        } else {
            return 0;
        }
    }

    public function fetchDepartmentMails(Request $request)
    {
        $departments = Department::all();
        foreach ($departments as $key => $department) {
            
        }
    }
}
