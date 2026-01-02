/*!
* Start Bootstrap - Freelancer v7.0.7 (https://startbootstrap.com/theme/freelancer)
* Copyright 2013-2023 Start Bootstrap
* Licensed under MIT (https://github.com/StartBootstrap/startbootstrap-freelancer/blob/master/LICENSE)
*/
//
// Scripts
// 

window.addEventListener('DOMContentLoaded', event => {

    // Navbar shrink function
    var navbarShrink = function () {
        const navbarCollapsible = document.body.querySelector('#mainNav');
        if (!navbarCollapsible) {
            return;
        }
        if (window.scrollY === 0) {
            navbarCollapsible.classList.remove('navbar-shrink')
        } else {
            navbarCollapsible.classList.add('navbar-shrink')
        }

    };

    // Shrink the navbar 
    navbarShrink();

    // Shrink the navbar when page is scrolled
    document.addEventListener('scroll', navbarShrink);

    // Activate Bootstrap scrollspy on the main nav element
    const mainNav = document.body.querySelector('#mainNav');
    if (mainNav) {
        new bootstrap.ScrollSpy(document.body, {
            target: '#mainNav',
            rootMargin: '0px 0px -40%',
        });
    };

    // Collapse responsive navbar when toggler is visible
    const navbarToggler = document.body.querySelector('.navbar-toggler');
    const responsiveNavItems = [].slice.call(
        document.querySelectorAll('#navbarResponsive .nav-link')
    );
    responsiveNavItems.map(function (responsiveNavItem) {
        responsiveNavItem.addEventListener('click', () => {
            if (window.getComputedStyle(navbarToggler).display !== 'none') {
                navbarToggler.click();
            }
        });
    });

    // Contact Form Submission
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Add Bootstrap validation classes
            contactForm.classList.add('was-validated');
            
            // Check if form is valid
            if (!contactForm.checkValidity()) {
                return;
            }
            
            const submitButton = document.getElementById('submitButton');
            const successMessage = document.getElementById('submitSuccessMessage');
            const errorMessage = document.getElementById('submitErrorMessage');
            
            // Get form data
            const formData = {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                category: document.getElementById('category').value,
                message: document.getElementById('message').value,
                to: 'admin@klypton.com'
            };
            
            // Security validation - check for dangerous content
            const dangerousPatterns = /<script|<\/script|javascript:|on\w+\s*=|<iframe|<object|<embed/i;
            if (dangerousPatterns.test(formData.name) || 
                dangerousPatterns.test(formData.message) || 
                dangerousPatterns.test(formData.email)) {
                errorMessage.querySelector('div').textContent = 'Invalid characters detected.';
                errorMessage.classList.remove('d-none');
                return;
            }
            
            // Length validation
            if (formData.message.length > 5000) {
                errorMessage.querySelector('div').textContent = 'Message too long (max 5000 characters).';
                errorMessage.classList.remove('d-none');
                return;
            }
            
            if (formData.name.length > 100) {
                errorMessage.querySelector('div').textContent = 'Name too long (max 100 characters).';
                errorMessage.classList.remove('d-none');
                return;
            }
            
            // Disable button while submitting
            submitButton.disabled = true;
            submitButton.textContent = 'Sending...';
            
            // Hide any previous messages
            successMessage.classList.add('d-none');
            errorMessage.classList.add('d-none');
            
            // Send email via fetch to backend endpoint
            fetch('/contact.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => {
                if (response.ok) {
                    return response.json();
                }
                throw new Error('Network response was not ok');
            })
            .then(data => {
                // Show success message
                successMessage.classList.remove('d-none');
                contactForm.reset();
                contactForm.classList.remove('was-validated');
            })
            .catch(error => {
                // Show error message
                errorMessage.classList.remove('d-none');
                console.error('Error:', error);
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.textContent = 'Send';
            });
        });
    }

    // Portfolio Modal Video - Load video on open, stop on close
    document.querySelectorAll('.portfolio-modal').forEach(modal => {
        modal.addEventListener('shown.bs.modal', function () {
            const iframe = this.querySelector('iframe[data-src]');
            if (iframe && iframe.dataset.src) {
                iframe.src = iframe.dataset.src;
            }
        });
        modal.addEventListener('hidden.bs.modal', function () {
            const iframe = this.querySelector('iframe');
            if (iframe) {
                iframe.src = '';
            }
        });
    });

    // Mobile detection helper
    const isMobileDevice = () => {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth < 992;
    };

    // Portfolio items - on mobile, go directly to YouTube instead of opening modal
    document.querySelectorAll('.portfolio-item[data-youtube-id]').forEach(item => {
        item.addEventListener('click', function(e) {
            if (isMobileDevice()) {
                e.preventDefault();
                e.stopPropagation();
                const youtubeId = this.dataset.youtubeId;
                if (youtubeId) {
                    window.location.href = 'https://www.youtube.com/watch?v=' + youtubeId;
                }
            }
        });
    });

    // Direct download button - on mobile, show warning modal instead of downloading
    document.querySelectorAll('.download-btn-direct').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (isMobileDevice()) {
                e.preventDefault();
                const mobileModal = new bootstrap.Modal(document.getElementById('mobileDownloadModal'));
                mobileModal.show();
            }
        });
    });

    // Update footer year to current year
    document.querySelectorAll('.footer-year').forEach(el => {
        el.textContent = new Date().getFullYear();
    });

    // Fetch latest release from GitHub and update download buttons
    fetch('https://api.github.com/repos/klypton-media/klypton-releases/releases/latest')
        .then(response => response.json())
        .then(data => {
            const version = data.tag_name; // e.g., "v1.4.26"
            const versionDisplay = version.replace('v', 'ver '); // "ver 1.4.26"
            
            // Find the zip asset download URL
            let directDownloadUrl = data.html_url; // Fallback to release page
            if (data.assets && data.assets.length > 0) {
                const zipAsset = data.assets.find(asset => asset.name.endsWith('.zip'));
                if (zipAsset) {
                    directDownloadUrl = zipAsset.browser_download_url;
                }
            }
            
            // Update version text on all download buttons
            document.querySelectorAll('.download-version').forEach(el => {
                el.textContent = versionDisplay;
            });
            
            // Update direct download buttons (zip file)
            document.querySelectorAll('.download-btn-direct').forEach(btn => {
                btn.href = directDownloadUrl;
            });
            
            // Update GitHub download buttons (release page)
            document.querySelectorAll('.download-btn-github').forEach(btn => {
                btn.href = data.html_url;
            });
        })
        .catch(err => console.log('Could not fetch latest release:', err));

    // Track download button clicks
    document.querySelectorAll('.download-btn-direct, .download-btn-github').forEach(btn => {
        btn.addEventListener('click', function(e) {
            fetch('/log-download.php', {
                method: 'POST'
            }).catch(err => console.log('Download tracking error:', err));
        });
    });

    // Reveal docs section when clicking docs nav link
    document.querySelectorAll('.docs-nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            const docsSection = document.getElementById('docs');
            if (docsSection) {
                docsSection.classList.remove('d-none');
            }
        });
    });

});
