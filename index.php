<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>O Frasco do Rodrigo</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <meta name="theme-color" content="#1a1a2e">
</head>
<body>
    <div id="app">

        <!-- HEADER -->
        <header id="header">
            <h1>O Frasco do Rodrigo</h1>
            <p class="subtitle">Ou vai ao ginásio, ou paga.</p>
        </header>

        <!-- PIN GATE -->
        <section id="pin-gate" class="card">
            <h2>Quem és tu?</h2>
            <div id="user-select" class="user-buttons"></div>
            <div id="pin-form" class="pin-form" hidden>
                <label for="pin-input">PIN</label>
                <input type="password" id="pin-input" maxlength="6" inputmode="numeric" placeholder="****" autocomplete="off">
                <button id="pin-submit" class="btn btn-primary">Entrar</button>
                <p id="pin-error" class="error-text" hidden></p>
            </div>
        </section>

        <!-- DASHBOARD (hidden until auth) -->
        <main id="dashboard" hidden>

            <!-- JAR -->
            <section id="jar-section" class="card">
                <div id="jar-container">
                    <div id="jar">
                        <div id="jar-rim"></div>
                        <div id="jar-body">
                            <div id="jar-fill"></div>
                            <div id="jar-amount">€0,00</div>
                        </div>
                    </div>
                </div>
                <div id="jar-label">Saldo do Frasco</div>
            </section>

            <!-- CURRENT WEEK -->
            <section id="current-week" class="card">
                <h2>Esta Semana</h2>
                <div id="week-dates" class="week-dates"></div>
                <div id="week-dots" class="week-dots">
                    <div class="dot-container"><span class="dot-label">Seg</span><div class="dot" data-day="1"></div></div>
                    <div class="dot-container"><span class="dot-label">Ter</span><div class="dot" data-day="2"></div></div>
                    <div class="dot-container"><span class="dot-label">Qua</span><div class="dot" data-day="3"></div></div>
                    <div class="dot-container"><span class="dot-label">Qui</span><div class="dot" data-day="4"></div></div>
                    <div class="dot-container"><span class="dot-label">Sex</span><div class="dot" data-day="5"></div></div>
                    <div class="dot-container"><span class="dot-label">Sáb</span><div class="dot" data-day="6"></div></div>
                    <div class="dot-container"><span class="dot-label">Dom</span><div class="dot" data-day="7"></div></div>
                </div>
                <p id="week-count" class="week-count">0 / 7 dias</p>
                <div class="week-actions">
                    <button id="log-today-btn" class="btn btn-primary btn-big">Registar Hoje</button>
                </div>
            </section>

            <!-- STREAK -->
            <section id="streak-section" class="card">
                <div class="streak-display">
                    <span id="streak-count" class="streak-number">0</span>
                    <span class="streak-label">semanas consecutivas boas</span>
                </div>
            </section>

            <!-- PROJECTION -->
            <section id="projection-section" class="card" hidden>
                <p id="projection-message" class="projection-text"></p>
            </section>

            <!-- ACTIONS -->
            <section id="actions-section" class="card">
                <h2>Ações</h2>
                <div class="action-buttons">
                    <button id="log-past-btn" class="btn btn-secondary">Registar Dia Passado</button>
                    <button id="withdraw-btn" class="btn btn-danger">Levantar Dinheiro</button>
                </div>
            </section>

            <!-- HISTORY -->
            <section id="history-section" class="card">
                <h2>Histórico Semanal</h2>
                <div id="history-list"></div>
                <button id="load-more-btn" class="btn btn-secondary" hidden>Carregar Mais</button>
            </section>

            <!-- RULES -->
            <section id="rules-section" class="card">
                <h2>As Regras do Frasco</h2>
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
            </section>

        </main>

        <!-- MODALS -->
        <div id="modal-overlay" class="modal-overlay" hidden>
            <!-- Log past day modal -->
            <div id="modal-log-past" class="modal" hidden>
                <h3>Registar Dia Passado</h3>
                <label for="past-date-input">Data</label>
                <input type="date" id="past-date-input">
                <div class="modal-buttons">
                    <button class="btn btn-primary" id="past-date-submit">Registar</button>
                    <button class="btn btn-secondary modal-cancel">Cancelar</button>
                </div>
                <p id="past-date-error" class="error-text" hidden></p>
            </div>

            <!-- Withdraw modal -->
            <div id="modal-withdraw" class="modal" hidden>
                <h3>Levantar Dinheiro</h3>
                <label for="withdraw-amount">Valor (€)</label>
                <input type="number" id="withdraw-amount" min="0.01" max="100" step="0.01" placeholder="0,00">
                <label for="withdraw-note">Nota (opcional)</label>
                <input type="text" id="withdraw-note" maxlength="200" placeholder="Ex: Rodrigo pagou almoço">
                <div class="modal-buttons">
                    <button class="btn btn-danger" id="withdraw-submit">Levantar</button>
                    <button class="btn btn-secondary modal-cancel">Cancelar</button>
                </div>
                <p id="withdraw-error" class="error-text" hidden></p>
            </div>

            <!-- Confirm delete modal -->
            <div id="modal-delete" class="modal" hidden>
                <h3>Remover Registo</h3>
                <p>Tens a certeza que queres remover o registo de <strong id="delete-date-label"></strong>?</p>
                <div class="modal-buttons">
                    <button class="btn btn-danger" id="delete-confirm">Sim, Remover</button>
                    <button class="btn btn-secondary modal-cancel">Cancelar</button>
                </div>
            </div>
        </div>

    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>
