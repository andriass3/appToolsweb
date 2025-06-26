</main> <!-- Menutup tag main dari header.php -->

<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <div class="footer-brand">
                    <div class="footer-logo">
                        <i class="material-icons">build_circle</i>
                    </div>
                    <div class="footer-brand-text">
                        <h6 class="footer-title">Webtools Directory</h6>
                        <p class="footer-subtitle">Produktivitas dalam genggaman</p>
                    </div>
                </div>
            </div>
            
            <div class="footer-section">
                <h6 class="footer-section-title">Quick Links</h6>
                <div class="footer-links">
                    <?php
                    // Diasumsikan $base_url sudah ada dari header.php
                    $feedback_url = ($base_url ?? '/') . 'kritik-saran.php';
                    ?>
                    <a href="<?php echo htmlspecialchars($feedback_url); ?>" class="footer-link">
                        <i class="material-icons">feedback</i>
                        <span>Kritik dan Saran</span>
                    </a>
                    <a href="<?php echo $base_url; ?>" class="footer-link">
                        <i class="material-icons">home</i>
                        <span>Beranda</span>
                    </a>
                </div>
            </div>
            
            <div class="footer-section">
                <h6 class="footer-section-title">Connect</h6>
                <div class="footer-social">
                    <a href="https://app.andrias.web.id/api/" target="_blank" class="footer-social-link" title="API Services">
                        <i class="material-icons">api</i>
                    </a>
                    <a href="#" class="footer-social-link" title="Support" data-bs-toggle="modal" data-bs-target="#coffeeModal">
                        <i class="material-icons">support</i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="footer-divider"></div>
            <div class="footer-bottom-content">
                <div class="footer-copyright">
                    <span>&copy; <?php echo date("Y"); ?> Webtools Directory. Dibuat dengan</span>
                    <i class="material-icons heart-icon">favorite</i>
                    <span>untuk produktivitas Anda.</span>
                </div>
                <div class="footer-version">
                    <span class="version-badge">v1.0</span>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button id="back-to-top" class="back-to-top-btn" title="Kembali ke atas">
    <i class="material-icons">keyboard_arrow_up</i>
</button>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Back to Top Button Logic ---
    const backToTopButton = document.getElementById('back-to-top');
    if (backToTopButton) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopButton.style.display = 'flex';
                backToTopButton.style.opacity = '1';
                backToTopButton.style.transform = 'translateY(0)';
            } else {
                backToTopButton.style.opacity = '0';
                backToTopButton.style.transform = 'translateY(10px)';
                setTimeout(() => {
                    if (window.pageYOffset <= 300) {
                        backToTopButton.style.display = 'none';
                    }
                }, 300);
            }
        });
        
        backToTopButton.addEventListener('click', function() {
            window.scrollTo({ 
                top: 0, 
                behavior: 'smooth' 
            });
        });
    }

    // --- Enhanced Search Functionality ---
    const searchInput = document.getElementById('tool-search-input');
    const toolsGrid = document.getElementById('tools-grid');
    const noResults = document.getElementById('no-results');

    if (searchInput && toolsGrid) {
        // Add search icon animation
        const searchIcon = document.querySelector('.search-icon');
        
        searchInput.addEventListener('focus', function() {
            if (searchIcon) {
                searchIcon.style.color = '#667eea';
                searchIcon.style.transform = 'translateY(-50%) scale(1.1)';
            }
        });
        
        searchInput.addEventListener('blur', function() {
            if (searchIcon) {
                searchIcon.style.color = '#718096';
                searchIcon.style.transform = 'translateY(-50%) scale(1)';
            }
        });

        // Enhanced search with debouncing
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchTerm = this.value.toLowerCase().trim();
                const toolLinks = toolsGrid.querySelectorAll('.tool-card-link');
                let visibleCount = 0;

                toolLinks.forEach((link, index) => {
                    const toolName = link.getAttribute('data-tool-name');
                    const toolDescription = link.getAttribute('data-tool-description');
                    const isMatch = toolName.includes(searchTerm) || toolDescription.includes(searchTerm);
                    
                    if (isMatch) {
                        link.classList.remove('hidden');
                        link.style.display = 'block';
                        // Add staggered animation
                        link.style.animationDelay = `${index * 0.1}s`;
                        visibleCount++;
                    } else {
                        link.classList.add('hidden');
                        link.style.display = 'none';
                    }
                });

                if (noResults) {
                    if (visibleCount === 0 && searchTerm !== '') {
                        noResults.style.display = 'block';
                        noResults.style.animation = 'fadeInUp 0.5s ease-out';
                    } else {
                        noResults.style.display = 'none';
                    }
                }
            }, 300);
        });
    }

    // --- Tool Usage Tracking ---
    const toolSlugMeta = document.querySelector('meta[name="tool-slug-stats"]');
    if (toolSlugMeta) {
        const currentToolSlug = toolSlugMeta.getAttribute('content');
        if (currentToolSlug) {
            const trackUsageUrl = 'https://app.andrias.web.id/track_tool_usage.php';
            
            fetch(trackUsageUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ tool_slug: currentToolSlug })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    console.log('Tool usage tracked:', currentToolSlug);
                }
            })
            .catch(error => {
                console.error('Error tracking tool usage:', error);
            });
        }
    }

    // --- Enhanced Tool Card Interactions ---
    const toolCards = document.querySelectorAll('.tool-card');
    toolCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });

    // --- Navbar Scroll Effect ---
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 50) {
                navbar.style.background = 'rgba(255, 255, 255, 0.98)';
                navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
            } else {
                navbar.style.background = 'rgba(255, 255, 255, 0.95)';
                navbar.style.boxShadow = '0 1px 3px rgba(0, 0, 0, 0.1)';
            }
        });
    }

    // --- Loading States ---
    const links = document.querySelectorAll('a[href^="/tools/"]');
    links.forEach(link => {
        link.addEventListener('click', function() {
            this.classList.add('loading');
        });
    });

    // --- Intersection Observer for Animations ---
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe tool cards for scroll animations
    document.querySelectorAll('.tool-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });

    // --- Footer Animation ---
    const footer = document.querySelector('.footer');
    if (footer) {
        const footerObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    footer.classList.add('footer-visible');
                }
            });
        }, { threshold: 0.1 });
        
        footerObserver.observe(footer);
    }
});
</script>

<style>
/* Enhanced Footer Styles */
.footer {
    background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
    color: white;
    padding: 3rem 0 1rem 0;
    margin-top: auto;
    position: relative;
    overflow: hidden;
}

.footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="footerPattern" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="white" opacity="0.05"/></pattern></defs><rect width="100" height="100" fill="url(%23footerPattern)"/></svg>');
    pointer-events: none;
}

.footer-content {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 2rem;
    position: relative;
    z-index: 2;
    margin-bottom: 2rem;
}

.footer-section {
    display: flex;
    flex-direction: column;
}

.footer-brand {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.footer-logo {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.footer-logo i {
    font-size: 1.5rem;
    color: white;
}

.footer-brand-text {
    flex: 1;
}

.footer-title {
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.footer-subtitle {
    font-size: 0.9rem;
    color: #a0aec0;
    margin: 0;
    font-weight: 400;
}

.footer-section-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #e2e8f0;
    position: relative;
}

.footer-section-title::after {
    content: '';
    position: absolute;
    bottom: -0.5rem;
    left: 0;
    width: 30px;
    height: 2px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 1px;
}

.footer-links {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.footer-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    color: #cbd5e0;
    font-weight: 500;
    padding: 0.5rem 0;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 6px;
    position: relative;
}

.footer-link::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    transition: width 0.3s ease;
    border-radius: 6px;
}

.footer-link:hover::before {
    width: 3px;
}

.footer-link:hover {
    color: white;
    transform: translateX(8px);
    padding-left: 1rem;
}

.footer-link i {
    font-size: 1.1rem;
    opacity: 0.8;
}

.footer-social {
    display: flex;
    gap: 1rem;
}

.footer-social-link {
    width: 45px;
    height: 45px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #cbd5e0;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.footer-social-link:hover {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.footer-social-link i {
    font-size: 1.2rem;
}

.footer-bottom {
    position: relative;
    z-index: 2;
}

.footer-divider {
    height: 1px;
    background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.2) 50%, transparent 100%);
    margin-bottom: 1.5rem;
}

.footer-bottom-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.footer-copyright {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #a0aec0;
    font-size: 0.9rem;
    font-weight: 400;
}

.heart-icon {
    color: #f56565;
    font-size: 1rem;
    animation: heartbeat 2s infinite;
}

@keyframes heartbeat {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.version-badge {
    background: rgba(255, 255, 255, 0.1);
    color: #e2e8f0;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
}

/* Enhanced Back to Top Button */
.back-to-top-btn {
    display: none;
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transform: translateY(10px);
    cursor: pointer;
}

.back-to-top-btn:hover {
    transform: translateY(-5px) scale(1.05);
    box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
}

.back-to-top-btn i {
    font-size: 1.5rem;
}

/* Footer Animation */
.footer {
    opacity: 0;
    transform: translateY(30px);
    transition: all 0.8s ease;
}

.footer.footer-visible {
    opacity: 1;
    transform: translateY(0);
}

/* Responsive Design */
@media (max-width: 992px) {
    .footer-content {
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }
    
    .footer-section:first-child {
        grid-column: 1 / -1;
        margin-bottom: 1rem;
    }
}

@media (max-width: 768px) {
    .footer {
        padding: 2rem 0 1rem 0;
    }
    
    .footer-content {
        grid-template-columns: 1fr;
        gap: 2rem;
        text-align: center;
    }
    
    .footer-brand {
        justify-content: center;
    }
    
    .footer-links {
        align-items: center;
    }
    
    .footer-social {
        justify-content: center;
    }
    
    .footer-bottom-content {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .footer-copyright {
        justify-content: center;
    }
    
    .back-to-top-btn {
        width: 50px;
        height: 50px;
        bottom: 20px;
        right: 20px;
    }
    
    .back-to-top-btn i {
        font-size: 1.3rem;
    }
}

@media (max-width: 576px) {
    .footer {
        padding: 1.5rem 0 1rem 0;
    }
    
    .footer-brand {
        flex-direction: column;
        text-align: center;
        gap: 0.75rem;
    }
    
    .footer-logo {
        width: 45px;
        height: 45px;
    }
    
    .footer-social-link {
        width: 40px;
        height: 40px;
    }
    
    .footer-social-link i {
        font-size: 1.1rem;
    }
}

/* Ensure footer sticks to bottom */
html, body {
    height: 100%;
}

body {
    display: flex;
    flex-direction: column;
}

main {
    flex: 1 0 auto;
}

.footer {
    flex-shrink: 0;
}
</style>

</body>
</html>