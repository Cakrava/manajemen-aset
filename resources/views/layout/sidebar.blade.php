@extends('layout.main')
@section('menu')

    <nav class="pc-sidebar">
        <div class="navbar-wrapper">
            <div class="m-header" style="margin-top : -30px; margin-bottom: 50px;">
                <a href="{{ route('front.index') }}" class="b-brand text-primary">
                    <!-- ========   Change your logo from here   ============ -->
                    <h3 class="text-white">Komdigi</h3> <br> <small class="text-white">Network Management</small>
                    <!-- Anda bisa mengganti text Komdigi dengan logo image jika ada -->
                    {{-- --}}
                    <img src="{{ asset('asset/image/top_logo.png') }}" style="max-width: 220px; max-height: 220px;">
                </a>
            </div>

            @if(session('role') == 'admin')
                <div class="navbar-content">
                    <ul class="pc-navbar">
                        <li class="pc-item">
                            <div style="background-color: rgba(0, 157, 255, 0.264); border-radius: 10px; margin: 0 10px;">
                                <a href="{{ route('panel.dashboard') }}" class="pc-link">
                                    <span class="pc-micon"><i class="ti ti-dashboard"></i></span>
                                    <span class="pc-mtext">Dashboard</span>
                                </a>
                            </div>
                        </li>

                        <li class="pc-item pc-caption">
                            <label>Core Vault</label>
                            <i class="ti ti-server"></i>
                        </li>
                        <li class="pc-item">
                            <a href="{{ route('panel.stored-device') }}" class="pc-link">
                                <span class="pc-micon"><i class="ti ti-box"></i></span>
                                <span class="pc-mtext">Inventory</span>
                            </a>
                        </li>
                        <li class="pc-item">
                            <a href="{{ route('admin.deployment.view') }}" class="pc-link">
                                <span class="pc-micon"><i class="ti ti-truck-delivery"></i></span>
                                <span class="pc-mtext">Deployed Device</span>
                            </a>
                        </li>


                        <li class="pc-item pc-caption">
                            <label>Primary</label>
                            <i class="ti ti-server"></i>
                        </li>
                        <li class="pc-item">
                            <a href="{{ route('panel.device') }}" class="pc-link">
                                <span class="pc-micon"><i class="ti ti-device-desktop"></i></span>
                                <span class="pc-mtext">Device</span>
                            </a>
                        </li>
                       
                        <li class="pc-item">
                            <a href="{{ route('panel.client') }}" class="pc-link">
                                <span class="pc-micon"><i class="ti ti-building"></i></span>
                                <span class="pc-mtext">Institution</span>
                            </a>
                        </li>


                        <li class="pc-item pc-caption">
                            <label>Event</label>
                            <i class="ti ti-arrows-right-left"></i>
                        </li>
                        <li class="pc-item">
                            <a href="{{ route('admin.asset-flow.view') }}" class="pc-link">
                                <span class="pc-micon"><i class="ti ti-inbox"></i></span>
                                <span class="pc-mtext">Asset Flow</span>
                            </a>
                        </li>
                        
                        <li class="pc-item">
                            <a href="{{ route('admin.letter.view') }}" class="pc-link">
                                <span class="pc-micon"><i class="ti ti-receipt"></i></span>
                                <span class="pc-mtext">Letters</span>
                            </a>
                        </li>

                        <li class="pc-item">
                            <a href="{{ route('panel.ticket.admin-ticket') }}" class="pc-link">
                                <span class="pc-micon"><i class="ti ti-ticket"></i></span>
                                <span class="pc-mtext">Ticket</span>
                                {{-- Badge untuk User Ticket (menggunakan $ticketProcessIsExist yang sudah diisi di
                                AppServiceProvider) --}}
                                <span class="badge bg-danger rounded-pill ms-1 pc-msg-badge" id="sidebar-badge-user-ticket" {{--
                                    ID diperbaiki, warna bisa disesuaikan --}}
                                    style="{{ $ticketProcessIsExist ? '' : 'display: none;' }} width: 8px; height: 8px; padding: 0;">
                                    {{-- Titik tanpa teks --}}
                                </span>
                            </a>
                        </li>

                        <li class="pc-item">
                            <a href="{{ route('chat.index.admin') }}" class="pc-link">
                                <span class="pc-micon"><i class="ti ti-mail"></i></span>
                                <span class="pc-mtext">Message</span>

                                {{-- Badge untuk Admin --}}
                                <span class="badge bg-danger rounded-pill ms-1 pc-msg-badge" id="sidebar-badge-ticket-process"
                                    {{-- ID UNIK --}}
                                    style="{{ $showUnreadMessageBadge ? '' : 'display: none;' }} width: 8px; height: 8px; padding: 0;">
                                    {{-- Titik merah tanpa teks --}}
                                </span>
                            </a>
                        </li>
                        <li class="pc-item pc-caption">
                            <label>Additional</label>
                            <i class="ti ti-plus"></i>
                        </li>
                        <li class="pc-item">
                            <a href="{{ route('reports.index') }}" class="pc-link">
                                <span class="pc-micon"><i class="ti ti-chart-bar"></i></span>
                                <span class="pc-mtext">Report</span>
                            </a>
                        </li>
                       
                        <br>
                        <br>
                        <br>
                        <br>

                    </ul>
                </div>
            @endif

            @if(session('role') == 'user')
                <div class="navbar-content">
                    <ul class="pc-navbar">
                        <li class="pc-item">
                            <div style="background-color: rgba(0, 157, 255, 0.264); border-radius: 10px; margin: 0 10px;">
                                <a href="{{ route('panel.dashboard') }}" class="pc-link">
                                    <span class="pc-micon"><i class="ti ti-dashboard"></i></span>
                                    <span class="pc-mtext">Dashboard</span>
                                </a>
                            </div>
                        </li>


                        <li class="pc-item pc-caption">
                            <label>Action</label>
                            <i class="ti ti-arrows-right-left"></i>
                        </li>

                        <li class="pc-item">
                            <a href="{{ route('panel.ticket.user-ticket') }}" class="pc-link">
                                <span class="pc-micon"><i class="ti ti-ticket"></i></span>
                                <span class="pc-mtext"> Ticket</span>
                                <span class="badge bg-danger rounded-pill ms-1 pc-msg-badge" id="sidebar-badge-master-ticket"
                                    {{-- ID diperbaiki, warna bisa disesuaikan --}}
                                    style="{{ $ticketActive ? '' : 'display: none;' }} width: 8px; height: 8px; padding: 0;">
                                    {{-- Titik tanpa teks --}}
                                </span>
                            </a>
                        </li>
                        <li class="pc-item">
                            {{-- Pastikan route ini benar untuk user --}}
                            <a href="{{ route('panel.message.user-message') }}" class="pc-link">
                                <span class="pc-micon"><i class="ti ti-mail"></i></span>
                                <span class="pc-mtext">Message</span>

                                {{-- Badge untuk User --}}
                                <span class="badge bg-danger rounded-pill ms-1 pc-msg-badge" id="sidebar-message-badge-user"
                                    {{-- ID UNIK --}}
                                    style="{{ $showUnreadMessageBadge ? '' : 'display: none;' }} width: 8px; height: 8px; padding: 0;">
                                    {{-- Titik merah tanpa teks --}}
                                </span>
                            </a>
                        </li>
                        <li class="pc-item">
                            <a href="{{ route('histories.show') }}" class="pc-link">
                                <span class="pc-micon"><i class="ti ti-history"></i></span>
                                <span class="pc-mtext">My History</span>
                            </a>
                        </li>
                        <li class="pc-item pc-caption">
                            <label>Additional</label>
                            <i class="ti ti-plus"></i>
                        </li>

                        <li class="pc-item">
                            <a href="{{ route('histories.show') }}" class="pc-link">
                                <span class="pc-micon"><i class="ti ti-stack"></i></span>
                                <span class="pc-mtext">My Letter</span>
                            </a>
                        </li>
                        <br>
                        <br>
                        <br>
                        <br>

                    </ul>
                </div>
            @endif

            @if(session('role') == 'master')
                <div class="navbar-content">
                    <ul class="pc-navbar">
                        <li class="pc-item">
                            <div style="background-color: rgba(0, 157, 255, 0.264); border-radius: 10px; margin: 0 10px;">
                                <a href="{{ route('panel.dashboard') }}" class="pc-link">
                                    <span class="pc-micon"><i class="ti ti-dashboard"></i></span>
                                    <span class="pc-mtext">Dashboard</span>
                                </a>
                            </div>
                        </li>


                        <li class="pc-item pc-caption">
                            <label>Need Action </label>
                            <i class="ti ti-arrows-right-left"></i>
                        </li>
                        <li class="pc-item">
                            <a href="{{ route('panel.ticket.master-ticket') }}" class="pc-link">
                                <span class="pc-micon"><i class="ti ti-ticket"></i></span>
                                <span class="pc-mtext">Inbound Ticket</span>
                                <span class="badge bg-danger rounded-pill ms-1 pc-msg-badge" id="sidebar-badge-master-ticket"
                                {{-- ID diperbaiki, warna bisa disesuaikan --}}
                                style="{{ $ticketPendingIsExist ? '' : 'display: none;' }} width: 8px; height: 8px; padding: 0;">
                                {{-- Titik tanpa teks --}}
                            </span>
                        </a>
                    </li>
                    
                   
                   

                    <li class="pc-item pc-caption">
                        <label>Core Vault</label>
                        <i class="ti ti-server"></i>
                    </li>
                    <li class="pc-item">
                        <a href="{{ route('panel.stored-device') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-box"></i></span>
                            <span class="pc-mtext">Inventory</span>
                        </a>
                    </li>
                    <li class="pc-item">
                        <a href="{{ route('admin.deployment.view') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-truck-delivery"></i></span>
                            <span class="pc-mtext">Deployed Device</span>
                        </a>
                    </li>
                    <li class="pc-item pc-caption">
                        <label>Admin Task</label>
                        <i class="ti ti-server"></i>
                    </li>
                    
                    <li class="pc-item">
                        <a href="{{ route('admin.asset-flow.view') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-inbox"></i></span>
                            <span class="pc-mtext">Asset Flow</span>
                        </a>
                    </li>
                    
                   
                    <li class="pc-item">
                        <a href="{{ route('admin.letter.view') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-stack"></i></span>
                            <span class="pc-mtext">Letter</span>
                        </a>
                    </li>

                    <li class="pc-item">
                        <a href="{{ route('reports.index') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-chart-bar"></i></span>
                            <span class="pc-mtext">Report</span>
                        </a>
                    </li>
                  
                    
                        <br>
                        <br>
                        <br>
                        <br>

                    </ul>
                </div>
            @endif

        </div>
    </nav>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        if ('{{ auth()->check() ? "true" : "false" }}' === 'true') {
            const userId   = '{{ auth()->id() }}';
            const userRole = '{{ session("role") }}';

            // --- Toast Helper ---
            function showMessageToast(senderName, messagePreview) {
                // Hapus toast lama jika ada
                document.querySelectorAll('.rt-toast').forEach(el => el.remove());

                const toast = document.createElement('div');
                toast.className = 'rt-toast toast align-items-center text-bg-primary border-0 position-fixed bottom-0 end-0 m-3';
                toast.style.zIndex = 9999;
                toast.setAttribute('role', 'alert');
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">
                            <strong>💬 New Message</strong><br>
                            <span class="text-white-50">${senderName}</span><br>
                            <small>${messagePreview}</small>
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>`;
                document.body.appendChild(toast);
                const bsToast = new bootstrap.Toast(toast, { delay: 5000 });
                bsToast.show();
                toast.addEventListener('hidden.bs.toast', () => toast.remove());
            }

            // Cek apakah sedang di halaman chat
            function isOnChatPage() {
                return /\/(message|chat)/i.test(window.location.pathname);
            }

            function initSidebarListeners() {
                if (!window.Echo) return;

                // Public channel — semua role dengerin badgeticket untuk badge update
                window.Echo.channel('badgeticket')
                    .listen('.triger', function (data) {
                        // Tampilkan badge pesan di sidebar
                        const badgeAdmin = document.getElementById('sidebar-badge-ticket-process');
                        const badgeUser  = document.getElementById('sidebar-message-badge-user');
                        if (badgeAdmin) badgeAdmin.style.display = '';
                        if (badgeUser)  badgeUser.style.display  = '';

                        // Toast: tampil jika bukan di halaman chat
                        if (!isOnChatPage() && data.sender_name) {
                            showMessageToast(data.sender_name, data.message_preview || '...');
                        }
                    });

                // User: tahu saat admin membalas — subscribe ke private chat.{userId}
                if (userRole !== 'admin' && userRole !== 'master') {
                    window.Echo.private(`chat.${userId}`)
                        .listen('.chat', function (eventData) {
                            const badgeUser = document.getElementById('sidebar-message-badge-user');
                            if (badgeUser) badgeUser.style.display = '';

                            // Toast untuk user saat admin balas dan user tidak di halaman chat
                            if (!isOnChatPage() && eventData.sender_data) {
                                const name    = eventData.sender_data?.profile?.name ?? 'Admin';
                                const preview = (eventData.message?.message ?? '').substring(0, 30);
                                showMessageToast(name, preview || '...');
                            }
                        });
                }
            }

            if (window.Echo) {
                initSidebarListeners();
            } else {
                setTimeout(initSidebarListeners, 500);
            }
        }
    });
    </script>

@endsection