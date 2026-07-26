<section id="book-a-table" class="book-a-table section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Book A Table</h2>
        <div><span>Book a</span> <span class="description-title">Table</span></div>
      </div><!-- End Section Title -->

      <div class="container">

        <div class="row g-0" data-aos="fade-up" data-aos-delay="100">

          <div class="col-lg-4 reservation-img" style="background-image: url(assets/img/reservation.jpg);"></div>

          <div class="col-lg-8 d-flex align-items-center reservation-form-bg" data-aos="fade-up" data-aos-delay="200">
            <form id="reservationForm" action="services/reservation/reservation.php" method="post" role="form" class="php-email-form" novalidate>
              <div class="row gy-4">
                <div class="col-lg-4 col-md-6">
                  <input type="text" name="name" class="form-control" id="name" placeholder="Your Name" required="">
                </div>
                <div class="col-lg-4 col-md-6">
                  <input type="email" class="form-control" name="email" id="email" placeholder="Your Email" required="">
                </div>
                <div class="col-lg-4 col-md-6">
                  <input type="text" class="form-control" name="phone" id="phone" placeholder="Your Phone (e.g., 0788123456)" required="">
                </div>
                <div class="col-lg-4 col-md-6">
                  <input type="date" name="date" class="form-control" id="date" placeholder="Date" required="">
                </div>
                <div class="col-lg-4 col-md-6">
                  <input type="time" name="time" class="form-control" id="time" placeholder="Time" required="">
                </div>
                <div class="col-lg-4 col-md-6">
                  <input type="number" class="form-control" name="people" id="people" placeholder="# of people" min="1" max="50" required="">
                </div>
              </div>

              <div class="form-group mt-3">
                <textarea class="form-control" name="message" rows="5" placeholder="Special requests (optional)"></textarea>
              </div>

              <div class="text-center mt-3">
                <div id="formMessages">
                  <div id="loadingMessage" class="loading-message" style="display: none;">
                    <div class="message-content">
                      <div class="message-icon">
                        <div class="spinner"></div>
                      </div>
                      <div class="message-text">
                        <h4>Processing Your Reservation</h4>
                        <p>Please wait while we secure your table...</p>
                      </div>
                    </div>
                  </div>
                  <div id="errorMessage" class="error-message" style="display: none;">
                    <div class="message-content">
                      <div class="message-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                      </div>
                      <div class="message-text">
                        <h4>Oops! Something Needs Attention</h4>
                        <p class="error-details"></p>
                      </div>
                    </div>
                  </div>
                  <div id="successMessage" class="success-message" style="display: none;">
                    <div class="message-content">
                      <div class="message-icon">
                        <i class="bi bi-check-circle"></i>
                      </div>
                      <div class="message-text">
                        <h4>Reservation Confirmed!</h4>
                        <p class="success-details"></p>
                      </div>
                    </div>
                  </div>
                </div>
                <button type="submit" id="submitBtn">Book a Table</button>
              </div>
            </form>
          </div><!-- End Reservation Form -->

        </div>

      </div>

    </section>

    <style>
    /* Modern Message Container Styles */
    .loading-message,
    .error-message,
    .success-message {
      margin: 20px 0;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      animation: slideIn 0.3s ease-out;
    }

    .message-content {
      display: flex;
      align-items: center;
      padding: 20px;
      gap: 15px;
    }

    .message-icon {
      flex-shrink: 0;
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
    }

    .message-text {
      flex: 1;
      text-align: left;
    }

    .message-text h4 {
      margin: 0 0 5px 0;
      font-size: 18px;
      font-weight: 600;
    }

    .message-text p {
      margin: 0;
      font-size: 14px;
      line-height: 1.4;
    }

    /* Loading Message */
    .loading-message {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }

    .loading-message .message-icon {
      background: rgba(255, 255, 255, 0.2);
    }

    .spinner {
      width: 24px;
      height: 24px;
      border: 3px solid rgba(255, 255, 255, 0.3);
      border-top: 3px solid white;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    /* Error Message */
    .error-message {
      background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
      color: white;
    }

    .error-message .message-icon {
      background: rgba(255, 255, 255, 0.2);
    }

    /* Success Message */
    .success-message {
      background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
      color: white;
    }

    .success-message .message-icon {
      background: rgba(255, 255, 255, 0.2);
    }

    /* Animations */
    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    /* Responsive Design */
    @media (max-width: 576px) {
      .message-content {
        flex-direction: column;
        text-align: center;
        gap: 10px;
      }

      .message-text {
        text-align: center;
      }

      .message-icon {
        width: 40px;
        height: 40px;
        font-size: 18px;
      }

      .message-text h4 {
        font-size: 16px;
      }

      .message-text p {
        font-size: 13px;
      }
    }
    </style>

    <script>
    // Wait for DOM to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Get form elements with error checking
        const form = document.getElementById('reservationForm');
        const submitBtn = document.getElementById('submitBtn');
        const loadingMessage = document.getElementById('loadingMessage');
        const errorMessage = document.getElementById('errorMessage');
        const successMessage = document.getElementById('successMessage');

        // Check if elements exist before proceeding
        if (!form || !submitBtn || !loadingMessage || !errorMessage || !successMessage) {
            console.error('Required form elements not found');
            return;
        }

        // Set minimum date to today
        const dateInput = document.getElementById('date');
        if (dateInput) {
            const today = new Date().toISOString().split('T')[0];
            dateInput.setAttribute('min', today);
        }

        // Set time constraints (9:00 AM - 10:30 PM)
        const timeInput = document.getElementById('time');
        if (timeInput) {
            timeInput.setAttribute('min', '09:00');
            timeInput.setAttribute('max', '22:30');
        }

        // Form submission handler
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Hide all messages
                loadingMessage.style.display = 'none';
                errorMessage.style.display = 'none';
                successMessage.style.display = 'none';
                
                // Show loading message
                loadingMessage.style.display = 'block';
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';

                // Create form data
                const formData = new FormData(form);

                // Send reservation request
                fetch('services/reservation/reservation.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    loadingMessage.style.display = 'none';
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Book a Table';

                    // Get message elements safely
                    const successDetails = successMessage.querySelector('.success-details');
                    const errorDetails = errorMessage.querySelector('.error-details');

                    if (data.success) {
                        if (successDetails) {
                            successDetails.textContent = data.message;
                        }
                        successMessage.style.display = 'block';
                        form.reset();
                        
                        // Hide success message after 10 seconds
                        setTimeout(() => {
                            successMessage.style.display = 'none';
                        }, 10000);
                    } else {
                        if (errorDetails) {
                            errorDetails.textContent = data.message;
                        }
                        errorMessage.style.display = 'block';
                        
                        // Hide error message after 8 seconds
                        setTimeout(() => {
                            errorMessage.style.display = 'none';
                        }, 8000);
                    }
                })
                .catch(error => {
                    loadingMessage.style.display = 'none';
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Book a Table';
                    
                    const errorDetails = errorMessage.querySelector('.error-details');
                    if (errorDetails) {
                        errorDetails.textContent = 'An error occurred. Please try again.';
                    }
                    errorMessage.style.display = 'block';
                    
                    console.error('Error:', error);
                    
                    // Hide error message after 8 seconds
                    setTimeout(() => {
                        errorMessage.style.display = 'none';
                    }, 8000);
                });
            });
        }

        // Phone number formatting
        const phoneInput = document.getElementById('phone');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\s/g, '');
                
                // Allow only numbers and optional +250 prefix
                if (value.startsWith('+250')) {
                    value = '+250' + value.substring(4).replace(/[^0-9]/g, '');
                } else {
                    value = value.replace(/[^0-9]/g, '');
                }
                
                e.target.value = value;
            });
        }
    });
    </script>