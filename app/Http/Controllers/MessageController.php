<?php

namespace App\Http\Controllers;

use App\Member;
use App\Message;
use App\Conversation;
use BotMan\BotMan\BotMan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\CreateMessageRequest;
use BotMan\Drivers\Facebook\FacebookDriver;
use Symfony\Component\HttpFoundation\Response;
use BotMan\Drivers\Facebook\Extensions\Element;
use BotMan\Drivers\Facebook\Extensions\ElementButton;
use BotMan\Drivers\Facebook\Extensions\GenericTemplate;

class MessageController extends Controller
{
    /**
     * The user repository instance.
     */
    protected $message;

    /**
     * Message Controller constructor.
     *
     * @param Message $message
     */
    public function __construct(Message $message)
    {
        $this->middleware('auth')->except(['publish']);

        $this->message = $message;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $messages = Message::paginate(10);

        return view('messages.index', ['messages' => $messages]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('messages.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CreateMessageRequest $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(CreateMessageRequest $request)
    {
        $response = $this->message->persist($request);

        return redirect()->route('show-message', $response); 
    }

    /**
     * Display the specified resource.
     *
     * @param  Message $message
     * 
     * @return \Illuminate\Http\Response
     */
    public function show(Message $message)
    {
        return view('messages.show', ['message' => $message]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Message $message
     * 
     * @return \Illuminate\Http\Response
     */
    public function edit(Message $message)
    {
        return view('messages.edit', ['message' => $message]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Message $message
     * 
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Message $message)
    {
        $thumbnail_path = null;

        if ($request->hasFile('thumbnail'))
        {
            $thumbnail_path = Storage::disk('s3')
                ->putFile('public/message-thumbnails', $request->file('thumbnail'), 'public');
        }

        $message->update([
            'title'         => $request->title,
            'description'   => $request->description,
            'thumbnail'     => $thumbnail_path ? $thumbnail_path : $message->thumbnail,
            'gender'        => $request->gender,
            'minimum_age'   => $request->minimum_age ? $request->minimum_age : 13,
            'updated_by'    => auth()->id()
        ]);

        return redirect()->route('show-message', $message);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Message $message
     *
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function destroy(Message $message)
    {
        $message->delete();

        return back();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Message $message
     * 
     * @return \Illuminate\Http\Response
     */
    public function publish(Message $message)
    {
        $bot = app('botman');

        // $members = Member::where('status', 1)->get();
        $members = Member::all();

        //$image_message = OutgoingMessage::create($message->title)->withAttachment($message->thumbnail);

        //$message_transit = $message->title . "\n" . $message->description;

        foreach($members as $member)
        {
            try {
                $bot->say($this->message($message), $member->user_platform_id, FacebookDriver::class);
            } catch (\Exception $exception) {
                continue;
            }

//            if ($member->platform->name == 'Facebook') {
//                $bot->say($message_transit, $member->user_platform_id, FacebookDriver::class);
//            }  elseif ($member->platform->name == 'Telegram') {
//                $bot->say($message_transit, $member->user_platform_id, TelegramDriver::class);
//            }
//              else {
//                $bot->say($this->message($message), $member->user_platform_id);
//            }
        }

        $message->update([
            'status'        => 'publish',
            'updated_by'    => auth()->id()
        ]);

        return response()->json([
            'response' => 'Message has been published successfully.'
        ], Response::HTTP_CREATED);
    }

    /**
     * Get a particular Message that has contents requested by Member..
     *
     * @param  BotMan $bot
     * 
     * @return void
     */
    public function showBotMan(BotMan $bot)
    {
        $extras = $bot->getMessage()->getExtras();        
        $apiReply = $extras['apiReply'];

        $title = $extras['apiParameters'][env('APP_ACTION') . '-messages'];

        $bot->typesAndWaits(1);
        $bot->reply($apiReply);

        $message = Message::with('details')->where('title', '=', $title)->first();            
            
        $bot->typesAndWaits(1);        

        $user = $bot->getUser();
        $member = Member::where('user_platform_id', '=', $user->getId())->first();

        if($message)
        {
            $bot->reply($this->details($message));

            if($member)
            {
                Conversation::create([
                    'intent'    => $title,
                    'member_id' => $member->id
                ]);
            }
        }
        else
        {

            $bot->reply('Oooh ' . $user->getFirstName() .
                ', kuna tatizo la kiufundi, endelea na mengine wakati tunarekesha hili.');
        }
    }

    /**
     * Display particular Message to a Member in Generic Template.
     *
     * @param  Message $message
     * 
     * @return GenericTemplate
     */
    public function message($message)
    {
        $url = null;

        if ($message->thumbnail)
            $url = env('S3_URL') . '/' . $message->thumbnail;
        else
            $url = env('S3_URL') . '/img/logo.jpg';

        $message = GenericTemplate::create()
                    ->addImageAspectRatio(GenericTemplate::RATIO_HORIZONTAL)
                    ->addElements([
                        Element::create($message->title)
                            ->subtitle($message->description)
                            ->image($url)
                            ->addButton(ElementButton::create('Fahamu zaidi')
                                ->payload($message->title)->type('postback'))
                    ]);

        return $message;
    }

    /**
     * Display a list of details for a particular Message to a Member in Generic Template.
     *
     * @param  Message $message
     * 
     * @return GenericTemplate
     */
    public function details($message)
    {                           
        $template_list = GenericTemplate::create()->addImageAspectRatio(GenericTemplate::RATIO_HORIZONTAL);
             
        foreach($message->details as $detail)
        {
            $url = null;

            if ($message->thumbnail)
                $url = env('AWS_URL') . '/' . $message->thumbnail;
            else
                $url = env('APP_URL') . '/img/logo.jpg';

            $template_list->addElements([
                Element::create($detail->title)
                    ->subtitle($detail->description)
                    ->image($url)
                    ->addButton(ElementButton::create('Fahamu zaidi')
                        ->payload($detail->title)->type('postback'))
            ]);
        } 

        return $template_list;
    }
}
