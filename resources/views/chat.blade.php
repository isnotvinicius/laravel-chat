<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Chat App') }}
        </h2>
    </x-slot>

    <!-- CSRF Token and Vite Scripts -->
    <x-slot name="head">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @vite(['resources/js/app.js'])
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex bg-gray-900 text-white shadow-lg rounded-lg overflow-hidden">

                <!-- Sidebar: Available Chats -->
                <div class="w-full md:w-1/3 bg-gray-800 border-r border-gray-700 p-4">
                    <h3 class="text-lg font-semibold text-gray-200 mb-4">Available Chats</h3>
                    <ul class="space-y-2">
                        @foreach($chats as $chat)
                            <li class="flex justify-between items-center p-2 rounded-lg hover:bg-gray-700">
                                <span class="text-gray-400">{{ $chat->name }}</span>
                                @if(auth()->user()->chats->contains($chat))
                                    <form action="{{ route('chat.leave', $chat) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-sm text-red-500 hover:text-red-400">Leave</button>
                                    </form>
                                @else
                                    <form action="{{ route('chat.join', $chat) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-sm text-blue-500 hover:text-blue-400">Join</button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Chat Area -->
                <div class="w-full md:w-2/3 flex flex-col bg-gray-800">
                    @if($userChats->isEmpty())
                        <div class="flex-1 flex items-center justify-center">
                            <p class="text-gray-500">Join a chat to start messaging!</p>
                        </div>
                    @else
                        @foreach($userChats as $chat)
                            @if($loop->first)
                                <div class="flex-1 flex flex-col">
                                    <div class="p-4 border-b border-gray-700">
                                        <h3 class="text-lg font-semibold text-gray-200">{{ $chat->name }}</h3>
                                    </div>
                                    <div class="chat-box flex-1 p-4 overflow-y-auto" id="chat-{{ $chat->id }}">
                                        @foreach($chat->messages as $message)
                                            <div class="message {{ auth()->id() === $message->user_id ? 'mine' : 'other' }} rounded-lg p-3 mb-2">
                                                <div class="flex items-baseline">
                                                    <span class="font-semibold {{ auth()->id() === $message->user_id ? 'text-blue-500' : 'text-gray-400' }} mr-2">
                                                        {{ $message->user->name }}
                                                    </span>
                                                    <span class="text-xs {{ auth()->id() === $message->user_id ? 'text-gray-200' : 'text-gray-500' }}">
                                                        {{ $message->created_at->format('H:i') }}
                                                    </span>
                                                </div>
                                                <p>{{ $message->content }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                    <form id="message-form-{{ $chat->id }}" class="p-4 border-t border-gray-700">
                                        @csrf
                                        <div class="flex space-x-2">
                                            <input type="text" name="content" placeholder="Type a message..." required
                                                   class="flex-1 p-2 border border-gray-600 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <button type="submit"
                                                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                                                Send
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Initialize Laravel Echo for each joined chat
            try {
                if (!window.Echo) {
                    throw new Error('Laravel Echo not initialized');
                }
                
                @foreach($userChats as $chat)
                    window.Echo.private('chat.{{ $chat->id }}')
                        .listen('MessageSent', (e) => {
                            console.log("EVENT", e);

                            // Get the correct chat box to display the new message
                            const chatBox = document.getElementById(`chat-{{ $chat->id }}`);
                            
                            // Create a new message div
                            const messageDiv = document.createElement('div');
                            const isMine = e.message.user.id === {{ auth()->id() }};
                            messageDiv.className = `message ${isMine ? 'mine' : 'other'} rounded-lg p-3 mb-2`;

                            // Add the content to the message div
                            messageDiv.innerHTML = `
                                <div class="flex items-baseline">
                                    <span class="font-semibold ${isMine ? 'text-blue-500' : 'text-gray-400'} mr-2">
                                        ${e.message.user.name}
                                    </span>
                                    <span class="text-xs ${isMine ? 'text-gray-200' : 'text-gray-500'}">
                                        ${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                    </span>
                                </div>
                                <p>${e.message.content}</p>
                            `;
                            
                            // Append the new message to the chat box
                            chatBox.appendChild(messageDiv);

                            // Scroll to the bottom of the chat
                            chatBox.scrollTop = chatBox.scrollHeight;
                        });
                @endforeach

            } catch (error) {
                console.error('Echo initialization failed:', error.message);
            }

            // Handle message sending
            const forms = document.querySelectorAll('[id^="message-form-"]');
            forms.forEach(form => {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const chatId = form.id.split('-')[2];
                    const contentInput = form.querySelector('input[name="content"]');
                    const content = contentInput.value.trim();
                    if (!content) {
                        console.log('Empty message, skipping');
                        return;
                    }

                    try {
                        // Send to server
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                        if (!csrfToken) {
                            throw new Error('CSRF token not found');
                        }
                        const response = await fetch(`/chat/message/${chatId}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ content }),
                        });

                        const result = await response.json();
                        console.log(result);
                        if (!response.ok) {
                            console.error('Error:', result.error);
                            alert('Failed to send message: ' + result.error);
                        }
                    } catch (error) {
                        console.error('Fetch error:', error.message);
                        alert('Error: ' + error.message);
                    }

                    form.reset();
                });
            });
        });
    </script>
</x-app-layout>
