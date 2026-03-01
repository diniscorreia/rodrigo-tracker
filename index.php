<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>O Frasco do Rodrigo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <meta name="theme-color" content="#0a0a0f">
</head>
<body>
    <div id="app">

        <!-- HEADER -->
        <header id="header">
            <div class="header-left">
                <img src="assets/img/logo-bp.svg" alt="Balola's Programme" class="header-logo">
            </div>
            <div class="header-center">
                <h1>O Frasco do Rodrigo</h1>
                <p class="subtitle">Ou vai ao ginásio, ou paga.</p>
            </div>
            <div class="header-icons">
                <button id="history-btn" class="icon-btn" title="Histórico">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </button>
                <button id="rules-btn" class="icon-btn" title="Regras">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                </button>
                <button id="gear-btn" class="icon-btn" title="Ações">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                </button>
            </div>
        </header>

        <!-- DASHBOARD (always visible) -->
        <main id="dashboard">

            <!-- BALANCE -->
            <section id="jar-section" class="card">
                <div id="jar-amount" class="jar-amount zero">€0,00</div>
                <div id="jar-label">Saldo do Frasco</div>
            </section>

            <!-- CURRENT WEEK -->
            <section id="current-week" class="card">
                <div id="week-ring-container">
                    <svg id="week-ring" class="week-progress-ring" viewBox="0 0 200 200">
                        <defs>
                            <linearGradient id="week-grad-low" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#ff9800"/>
                                <stop offset="100%" stop-color="#ffca28"/>
                            </linearGradient>
                            <linearGradient id="week-grad-good" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#00e676"/>
                                <stop offset="100%" stop-color="#69f0ae"/>
                            </linearGradient>
                        </defs>
                        <circle class="ring-bg" cx="100" cy="100" r="85" />
                        <circle id="week-ring-fill" class="week-ring-progress" cx="100" cy="100" r="85" />
                    </svg>
                    <div id="week-ring-label">0 vezes<span class="week-ring-sublabel">esta semana</span></div>
                </div>
                <div id="week-dates" class="week-dates"></div>
                <div id="week-dots" class="week-dots">
                    <div class="dot-container"><div class="dot" data-day="1"></div><span class="dot-label">Seg</span></div>
                    <div class="dot-container"><div class="dot" data-day="2"></div><span class="dot-label">Ter</span></div>
                    <div class="dot-container"><div class="dot" data-day="3"></div><span class="dot-label">Qua</span></div>
                    <div class="dot-container"><div class="dot" data-day="4"></div><span class="dot-label">Qui</span></div>
                    <div class="dot-container"><div class="dot" data-day="5"></div><span class="dot-label">Sex</span></div>
                    <div class="dot-container"><div class="dot" data-day="6"></div><span class="dot-label">Sáb</span></div>
                    <div class="dot-container"><div class="dot" data-day="7"></div><span class="dot-label">Dom</span></div>
                </div>
            </section>

            <!-- STREAK -->
            <section id="streak-section" class="card">
                <div class="streak-display">
                    <div id="streak-count" class="streak-number">0</div>
                    <div class="streak-label">semanas consecutivas boas</div>
                </div>
            </section>

            <!-- PROJECTION -->
            <section id="projection-section" class="card" hidden>
                <p id="projection-message" class="projection-text"></p>
            </section>

        </main>

        <!-- MODALS -->
        <div id="modal-overlay" class="modal-overlay">
            <!-- History modal -->
            <div id="modal-history" class="modal modal-tall" hidden>
                <button class="modal-close modal-cancel" aria-label="Fechar"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
                <h3>Histórico Semanal</h3>
                <div id="history-list" class="history-scroll"></div>
                <div class="modal-buttons">
                    <button id="load-more-btn" class="btn btn-secondary" hidden>Carregar Mais</button>
                </div>
            </div>

            <!-- Admin actions modal -->
            <div id="modal-admin" class="modal" hidden>
                <button class="modal-close modal-cancel" aria-label="Fechar"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
                <h3>Ações</h3>
                <div class="action-buttons">
                    <button id="log-past-btn" class="btn btn-secondary">Registar Dia Passado</button>
                    <button id="withdraw-btn" class="btn btn-danger">Levantar Dinheiro</button>
                </div>
            </div>

            <!-- PIN modal -->
            <div id="modal-pin" class="modal" hidden>
                <button class="modal-close modal-cancel" aria-label="Fechar"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
                <h3>Introduz o PIN</h3>
                <label for="pin-input">PIN</label>
                <input type="password" id="pin-input" maxlength="6" inputmode="numeric" placeholder="****" autocomplete="off">
                <div class="modal-buttons">
                    <button class="btn btn-primary" id="pin-submit">Entrar</button>
                </div>
                <p id="pin-error" class="error-text" hidden></p>
            </div>

            <!-- Rules modal -->
            <div id="modal-rules" class="modal" hidden>
                <button class="modal-close modal-cancel" aria-label="Fechar"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
                <h3>As Regras do Frasco</h3>
                <div class="rules-content">
                    <table class="rules-table">
                        <thead>
                            <tr><th>Dias/Semana</th><th>Resultado</th></tr>
                        </thead>
                        <tbody>
                            <tr class="rule-bad"><td>0–3 dias</td><td>-€1,00</td></tr>
                            <tr class="rule-neutral"><td>4 dias</td><td>€0,00</td></tr>
                            <tr class="rule-good"><td>5 dias</td><td>+€0,75</td></tr>
                            <tr class="rule-great"><td>6+ dias</td><td>+€1,00</td></tr>
                        </tbody>
                    </table>
                    <div class="rules-extras">
                        <p><strong>Bónus:</strong> A cada 4 semanas consecutivas boas (5+ dias), +€0,50 extra!</p>
                        <p><strong>Semana:</strong> Segunda a Domingo.</p>
                        <p><strong>Competição:</strong> CrossFit Lisbon, 5 de Junho de 2025. O desafio termina a 30 de Maio.</p>
                        <p><strong>Início:</strong> Saldo começa em €0,00.</p>
                    </div>
                </div>
            </div>

            <!-- Log past day modal -->
            <div id="modal-log-past" class="modal" hidden>
                <button class="modal-close modal-cancel" aria-label="Fechar"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
                <h3>Registar Dia Passado</h3>
                <label for="past-date-input">Data</label>
                <input type="date" id="past-date-input">
                <div class="modal-buttons">
                    <button class="btn btn-primary" id="past-date-submit">Registar</button>
                </div>
                <p id="past-date-error" class="error-text" hidden></p>
            </div>

            <!-- Withdraw modal -->
            <div id="modal-withdraw" class="modal" hidden>
                <button class="modal-close modal-cancel" aria-label="Fechar"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
                <h3>Levantar Dinheiro</h3>
                <label for="withdraw-amount">Valor (€)</label>
                <input type="number" id="withdraw-amount" min="0.01" max="100" step="0.01" placeholder="0,00">
                <label for="withdraw-note">Nota (opcional)</label>
                <input type="text" id="withdraw-note" maxlength="200" placeholder="Ex: Rodrigo pagou almoço">
                <div class="modal-buttons">
                    <button class="btn btn-danger" id="withdraw-submit">Levantar</button>
                </div>
                <p id="withdraw-error" class="error-text" hidden></p>
            </div>

            <!-- Confirm delete modal -->
            <div id="modal-delete" class="modal" hidden>
                <button class="modal-close modal-cancel" aria-label="Fechar"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
                <h3>Remover Registo</h3>
                <p>Tens a certeza que queres remover o registo de <strong id="delete-date-label"></strong>?</p>
                <div class="modal-buttons">
                    <button class="btn btn-danger" id="delete-confirm">Sim, Remover</button>
                </div>
            </div>
        </div>

        <!-- FAB: Log Today -->
        <button id="fab-log" class="fab" title="Registar hoje">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </button>

    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>
