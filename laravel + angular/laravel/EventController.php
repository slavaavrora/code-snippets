<?php

namespace App\Http\Controllers;

use App\Currency;
use App\Event;
use App\Group;
use App\Services\EventService;
use App\Services\EventTypeService;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * @return EventService
     */
    protected function eventService()
    {
        return resolve('App\Services\EventService');
    }

    /**
     * @return EventTypeService
     */
    protected function eventTypeService()
    {
        return resolve('App\Services\EventTypeService');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param null $slug
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $slug = null)
    {
        $service = $this->eventService();
        $ts = $this->eventTypeService();

        $tag = ($slug && $slug !== 'free') ? $ts->type($slug)
            : $ts->defaultType();

        /** @var Builder $eventQuery */
        $eventQuery = $service->filterByTypeSlug($service->eventByCity(), $tag);

        $filter = $request->get('filter'); //create
        $date = $request->get('date', null);
        $eventQuery = $service->filterByFilter($eventQuery, $filter, $date);

        $last = $request->get('last', 0);
        $count = 18;
        $events = $eventQuery->skip($last)->take($count)->get();

        $groupH = null;
        $groupV = null;

        if ($events->count() >= 6) {
            $groupH = $this->eventService()->group(Group::TYPE_HORIZONTAL,
                $request->get('page'));
            $count = $groupH ? $count - 3 : $count;
        }

        if ($events->count() >= 10) {
            $groupV = $this->eventService()
                ->group(Group::TYPE_VERTICAL, $request->get('page'));
            $count = $groupV ? $count - 2 : $count;
        }

        $events = $events->slice(0, $count);
        $last += $events->count();
        $hasMore = $events->count() >= $count;

        if ($request->wantsJson()) {
            return response()->json(
                [
                    'hasMore' => $hasMore,
                    'last' => $last,
                    'html' => view('pages.events.items',
                        compact('events', 'groupH', 'groupV'))->render(),
                ]
            );
        }

        $tags = $ts->all();

        $headerBlocks = $this->eventService()->headerEvents();

        return view(
            'pages.events.index',
            compact('headerBlocks', 'tags', 'tag', 'events', 'hasMore', 'last',
                'filter', 'date', 'groupH', 'groupV')
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        /** @var Event $event */
        $event = $this->eventService()->eventQuery()
            ->with('photo', 'photos', 'city')->findOrFail($id);
        $tags = $this->eventTypeService()->all();

        $author = null;
        $event->append(['liked', 'going', 'favoriting']);

        $similarEvents = $this->eventService()
            ->similar($event->eventTypes->modelKeys());

        $comments = $event->comments()
            ->take(30)
            ->orderBy('created_at', 'desc')
            ->get();

        return view(
            'pages.events.show',
            compact('event', 'tags', 'author', 'similarEvents', 'comments')
        );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $currencies = Currency::all();
        $types = $this->eventTypeService()->all();

        return response()->json(compact('currencies', 'types'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $this->eventService()->validate($request->all());

        $event = \DB::transaction(
            function () use ($data) {
                return $this->eventService()->create($data);
            }
        );

        if (empty($event)) {
            return response()->json($this->eventService()->errors(), 400);
        }

        $request->user()->events()->attach($event);
        $event->load(['lang', 'eventTypes']);

        return response()->json(compact('event'));
    }

    /**
     * @param Request $request
     * @param         $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function like(Request $request, $id)
    {
        $event = $this->eventService()->event($id);

        if ($request->user()) {
            $event->likes()->toggle($request->user());
        }

        $event->like_counter = $event->likes()->count();

        return response()->json(
            [
                'done' => $event->save(),
                'liked' => $event->liked,
                'like_counter' => $event->like_counter,
            ]
        );
    }

    /**
     * @param Request $request
     * @param         $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function go(Request $request, $id)
    {
        $event = $this->eventService()->event($id);

        if ($request->user()) {
            $event->goes()->toggle($request->user());
        }

        $event->go_counter = $event->goes()->count();

        return response()->json(
            [
                'done' => $event->save(),
                'going' => $event->going,
                'go_counter' => $event->go_counter,
            ]
        );
    }

    /**
     * @param Request $request
     * @param         $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function favorite(Request $request, $id)
    {
        $event = $this->eventService()->event($id);
        /** @var User $user */
        $user = $request->user();

        if ($user) {
            $user->favoriteEvents()->toggle($event);
        }

        return response()->json(
            [
                'done' => $event->save(),
                'favoriting' => $event->favoriting,
            ]
        );
    }
}
