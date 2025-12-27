    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-top">
                <div class="footer-grid">
                    <!-- About -->
                    <div class="footer-col">
                        <div class="footer-brand">
                            <span class="logo-text">Cyber<strong>Core</strong></span>
                        </div>
                        <p class="footer-desc">
                            Alojamento web profissional em Portugal. Infraestrutura de alto desempenho 
                            com suporte técnico 24/7 em português.
                        </p>
                        <div class="footer-social">
                            <a href="#" aria-label="Facebook" class="social-link">
                                <svg width="20" height="20" fill="currentColor"><path d="M9.198 21.5h4v-8.01h3.604l.396-3.98h-4V7.5a1 1 0 0 1 1-1h3v-4h-3a5 5 0 0 0-5 5v2.01h-2l-.396 3.98h2.396v8.01Z"/></svg>
                            </a>
                            <a href="#" aria-label="Twitter" class="social-link">
                                <svg width="20" height="20" fill="currentColor"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>
                            </a>
                            <a href="#" aria-label="LinkedIn" class="social-link">
                                <svg width="20" height="20" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6zM2 9h4v12H2z"/><circle cx="4" cy="4" r="2"/></svg>
                            </a>
                        </div>
                    </div>

                    <!-- Products -->
                    <div class="footer-col">
                        <h4 class="footer-title">Produtos</h4>
                        <ul class="footer-links">
                            <li><a href="/hosting.php">Alojamento Web</a></li>
                            <li><a href="/wordpress.php">WordPress</a></li>
                            <li><a href="/vps.php">VPS Cloud</a></li>
                            <li><a href="/dedicated.php">Servidores Dedicados</a></li>
                            <li><a href="/domains.php">Domínios</a></li>
                            <li><a href="/ssl.php">Certificados SSL</a></li>
                        </ul>
                    </div>

                    <!-- Company -->
                    <div class="footer-col">
                        <h4 class="footer-title">Empresa</h4>
                        <ul class="footer-links">
                            <li><a href="/about.php">Sobre Nós</a></li>
                            <li><a href="/datacenter.php">Datacenter</a></li>
                            <li><a href="/careers.php">Carreiras</a></li>
                            <li><a href="/partners.php">Parceiros</a></li>
                            <li><a href="/blog.php">Blog</a></li>
                        </ul>
                    </div>

                    <!-- Support -->
                    <div class="footer-col">
                        <h4 class="footer-title">Suporte</h4>
                        <ul class="footer-links">
                            <li><a href="/support.php">Centro de Ajuda</a></li>
                            <li><a href="/kb.php">Base de Conhecimento</a></li>
                            <li><a href="/status.php">Estado dos Serviços</a></li>
                            <li><a href="/contact.php">Contacto</a></li>
                        </ul>
                    </div>

                    <!-- Legal -->
                    <div class="footer-col">
                        <h4 class="footer-title">Legal</h4>
                        <ul class="footer-links">
                            <li><a href="/terms.php">Termos de Serviço</a></li>
                            <li><a href="/privacy.php">Política de Privacidade</a></li>
                            <li><a href="/sla.php">SLA</a></li>
                            <li><a href="/gdpr.php">GDPR</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="footer-bottom-inner">
                    <p class="footer-copy">
                        &copy; <?php echo date('Y'); ?> CyberCore. Todos os direitos reservados.
                    </p>
                    <div class="footer-payment">
                        <span class="payment-text">Pagamentos seguros</span>
                        <div class="payment-icons">
                            <img src="/assets/img/visa.svg" alt="Visa" width="40" height="24">
                            <img src="/assets/img/mastercard.svg" alt="Mastercard" width="40" height="24">
                            <img src="/assets/img/mbway.svg" alt="MB WAY" width="40" height="24">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="/assets/js/main.js"></script>
    <?php if (isset($extra_js)): ?>
        <?php foreach ((array)$extra_js as $js): ?>
            <script src="<?php echo htmlspecialchars($js); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
