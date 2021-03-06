<li class="top-notifications">
    <div class="message-center">
        @php
            if (!isset($notification->data['from_name'])) {
                $chat = \App\UserChat::with('fromUser')->find($notification->data['id']);
                $fromName = $chat->fromUser->name;
            } else {
                $fromName = $notification->data['from_name'];
            }
        @endphp
        <a href="{{ route('member.user-chat.index').'?user='.$notification->data['user_one'] }}">
            <div class="user-img">
                <span class="btn btn-circle btn-success"><i class="icon-envelope"></i></span>
            </div>
            <div class="mail-contnet">
                <span class="mail-desc m-0">@lang('email.newChat.subject')</span> 
                <small>{{ ucwords($fromName) }}</small>
                <span class="time">{{ \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $notification->created_at)->diffForHumans() }}</span>
            </div>
        </a>
    </div>
</li>
