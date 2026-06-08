<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body>

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">
            <div class="page-header">
                <div>
                    <h1>Messages</h1>
                    <p>View and respond to client and supplier messages.</p>
                </div>
                <button class="add-btn" type="button" id="openComposeModal">
                    <i data-lucide="pencil"></i>
                    Compose
                </button>
            </div>

            <div class="messages-layout">
                <!-- Message List -->
                <div class="message-list-card">
                    <div class="search-box" style="margin-bottom: 14px;">
                        <i data-lucide="search"></i>
                        <input type="text" placeholder="Search messages...">
                    </div>

                    <div class="message-item unread" onclick="openMessage(this, 'ABC Corporation', 'Re: Fuel Tank Project Update', 'Good day! We would like to follow up on the current status of the fuel storage tank fabrication. Please provide an update at your earliest convenience.', 'May 30, 2026')">
                        <div class="message-avatar corporate-avatar">A</div>
                        <div class="message-preview">
                            <div class="message-sender">ABC Corporation</div>
                            <div class="message-subject">Re: Fuel Tank Project Update</div>
                            <div class="message-snippet">We would like to follow up on the current status...</div>
                        </div>
                        <div class="message-meta">
                            <span class="message-time">May 30</span>
                            <span class="unread-dot"></span>
                        </div>
                    </div>

                    <div class="message-item unread" onclick="openMessage(this, 'Prime Chemicals', 'Payment Terms Clarification', 'We need clarification on the payment schedule for the chemical tank repair project. Is a 30% down payment acceptable instead of 50%?', 'May 29, 2026')">
                        <div class="message-avatar corporate-avatar">P</div>
                        <div class="message-preview">
                            <div class="message-sender">Prime Chemicals</div>
                            <div class="message-subject">Payment Terms Clarification</div>
                            <div class="message-snippet">We need clarification on the payment schedule...</div>
                        </div>
                        <div class="message-meta">
                            <span class="message-time">May 29</span>
                            <span class="unread-dot"></span>
                        </div>
                    </div>

                    <div class="message-item" onclick="openMessage(this, 'Steel Supply Co.', 'Delivery Confirmation', 'We confirm that the steel plates (6mm) have been delivered to your site on May 10, 2026. Please acknowledge receipt.', 'May 10, 2026')">
                        <div class="message-avatar supplier-avatar">S</div>
                        <div class="message-preview">
                            <div class="message-sender">Steel Supply Co.</div>
                            <div class="message-subject">Delivery Confirmation</div>
                            <div class="message-snippet">Steel plates (6mm) have been delivered...</div>
                        </div>
                        <div class="message-meta">
                            <span class="message-time">May 10</span>
                        </div>
                    </div>

                    <div class="message-item unread" onclick="openMessage(this, 'South Plant Inc.', 'Project Completion Certificate', 'Thank you for completing the water tank installation. Please send us the project completion certificate for our records.', 'May 5, 2026')">
                        <div class="message-avatar owner-avatar">S</div>
                        <div class="message-preview">
                            <div class="message-sender">South Plant Inc.</div>
                            <div class="message-subject">Project Completion Certificate</div>
                            <div class="message-snippet">Thank you for completing the water tank...</div>
                        </div>
                        <div class="message-meta">
                            <span class="message-time">May 5</span>
                            <span class="unread-dot"></span>
                        </div>
                    </div>

                    <div class="message-item" onclick="openMessage(this, 'MetalWorks PH', 'Quotation for Welding Rods', 'Please find attached our updated quotation for welding rods (7018) as requested. Prices are valid for 30 days.', 'May 3, 2026')">
                        <div class="message-avatar supplier-avatar">M</div>
                        <div class="message-preview">
                            <div class="message-sender">MetalWorks PH</div>
                            <div class="message-subject">Quotation for Welding Rods</div>
                            <div class="message-snippet">Please find attached our updated quotation...</div>
                        </div>
                        <div class="message-meta">
                            <span class="message-time">May 3</span>
                        </div>
                    </div>
                </div>

                <!-- Message View -->
                <div class="message-view-card" id="messageView">
                    <div class="message-empty-state" id="messageEmptyState">
                        <i data-lucide="message-square"></i>
                        <p>Select a message to read</p>
                    </div>

                    <div class="message-content" id="messageContent" style="display:none;">
                        <div class="message-view-header">
                            <div class="message-view-subject" id="msgViewSubject"></div>
                            <div class="message-view-meta">
                                <span id="msgViewSender"></span>
                                <span id="msgViewDate"></span>
                            </div>
                        </div>
                        <div class="message-view-body" id="msgViewBody"></div>

                        <div class="message-reply-box">
                            <textarea placeholder="Type your reply..." rows="4" id="msgReplyText"></textarea>
                            <div class="modal-actions" style="margin-top:12px;">
                                <button class="save-btn" onclick="sendReply()">
                                    <i data-lucide="send"></i>
                                    Send Reply
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- COMPOSE MODAL -->
    <div class="modal-overlay" id="composeModal">
        <div class="modal-card" style="max-width: 560px;">
            <div class="modal-header">
                <div>
                    <h2>Compose Message</h2>
                    <p>Send a message to a client or supplier.</p>
                </div>
                <button class="modal-close" type="button" id="closeComposeModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form id="composeForm">
                <div class="form-grid" style="grid-template-columns: 1fr;">
                    <div class="form-group">
                        <label>Recipient</label>
                        <select id="msgTo" required>
                            <option value="">Select recipient</option>
                            <option value="ABC Corporation">ABC Corporation (Client)</option>
                            <option value="South Plant Inc.">South Plant Inc. (Client)</option>
                            <option value="Prime Chemicals">Prime Chemicals (Client)</option>
                            <option value="Steel Supply Co.">Steel Supply Co. (Supplier)</option>
                            <option value="MetalWorks PH">MetalWorks PH (Supplier)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" id="msgSubject" required placeholder="Message subject">
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea id="msgBody" required rows="5" placeholder="Type your message here..."></textarea>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelCompose">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="send"></i>
                        Send Message
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
</body>
</html>