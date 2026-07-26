<section id="promotions" class="specials section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Promotions</h2>
        <div><span>Check Our</span> <span class="description-title">Promotions</span></div>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row">
          <div class="col-lg-3">
            <ul class="nav nav-tabs flex-column">
              <?php
              // Fetch active promotions from database
              if ($conn) {
                $promotion_query = "SELECT promotion_id, promotion_name, description, discount_type, discount_value, 
                                 minimum_order_amount, promotion_days, is_active, max_uses, current_uses
                                 FROM promotions 
                                 WHERE is_active = 1 AND deleted_at IS NULL 
                                 ORDER BY created_at DESC";
                $promotion_result = mysqli_query($conn, $promotion_query);
                
                $promotions = [];
                if ($promotion_result && mysqli_num_rows($promotion_result) > 0) {
                    while ($promotion = mysqli_fetch_assoc($promotion_result)) {
                        $promotions[] = $promotion;
                    }
                }
                
                // Display promotion tabs
                $tab_index = 1;
                foreach ($promotions as $index => $promotion) {
                    $active_class = $index === 0 ? 'active show' : '';
                    $tab_id = 'promo-tab-' . $tab_index;
                    
                    echo '<li class="nav-item">';
                    echo '<a class="nav-link ' . $active_class . '" data-bs-toggle="tab" href="#' . $tab_id . '">';
                    echo htmlspecialchars($promotion['promotion_name']);
                    echo '</a>';
                    echo '</li>';
                    $tab_index++;
                }
                
                // If no promotions, show message
                if (empty($promotions)) {
                    echo '<li class="nav-item">';
                    echo '<a class="nav-link active show" data-bs-toggle="tab" href="#no-promotions">No Promotions</a>';
                    echo '</li>';
                }
              }
              ?>
            </ul>
          </div>
          <div class="col-lg-9 mt-4 mt-lg-0">
            <div class="tab-content">
              <?php
              if (!empty($promotions)) {
                $tab_index = 1;
                foreach ($promotions as $promotion) {
                  $tab_id = 'promo-tab-' . $tab_index;
                  $active_class = $tab_index === 1 ? 'active show' : '';
                  
                  // Format discount display
                  $discount_text = '';
                  if ($promotion['discount_type'] === 'percentage') {
                    $discount_text = $promotion['discount_value'] . '% OFF';
                  } else {
                    $discount_text = 'Frw ' . number_format($promotion['discount_value'], 0) . ' OFF';
                  }
                  
                  // Parse promotion days
                  $weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                  $active_days = [];
                  if (!empty($promotion['promotion_days'])) {
                    $day_numbers = explode(',', $promotion['promotion_days']);
                    foreach ($day_numbers as $day_num) {
                      $day_num = trim($day_num);
                      if (isset($weekdays[$day_num])) {
                        $active_days[] = $weekdays[$day_num];
                      }
                    }
                  }
                  
                  echo '<div class="tab-pane ' . $active_class . '" id="' . $tab_id . '">';
                  echo '<div class="row">';
                  echo '<div class="col-lg-12 details order-2 order-lg-1">';
                  echo '<h3>' . htmlspecialchars($promotion['promotion_name']) . '</h3>';
                  echo '<div class="promotion-discount">' . $discount_text . '</div>';
                  
                  if (!empty($promotion['description'])) {
                    echo '<p class="fst-italic">' . htmlspecialchars($promotion['description']) . '</p>';
                  }
                  
                  echo '<div class="promotion-details d-flex justify-content-between flex-wrap">';
                  echo '<div class="detail-item d-flex align-items-center  gap-2"><strong>Minimum Order:</strong> <p style="font-size: 20px;">' . number_format($promotion['minimum_order_amount'], 0) . '</p></div>';
                  
                  if (!empty($active_days)) {
                    echo '<div class="detail-item"><strong>Active Days:</strong> ' . implode(', ', $active_days) . '</div>';
                  }
                  
                  if ($promotion['max_uses'] !== null) {
                    $remaining = $promotion['max_uses'] - $promotion['current_uses'];
                    echo '<div class="detail-item"><strong>Remaining Uses:</strong> ' . $remaining . '</div>';
                  }
                  
                  echo '</div>';
                  echo '</div>';
                  echo '</div>';
                  echo '</div>';
                  $tab_index++;
                }
              } else {
                echo '<div class="tab-pane active show" id="no-promotions">';
                echo '<div class="row">';
                echo '<div class="col-12 text-center">';
                echo '<div class="no-promotions-message">';
                echo '<i class="bi bi-gift" style="font-size: 48px; color: var(--accent-color); margin-bottom: 20px;"></i>';
                echo '<h4>No Active Promotions</h4>';
                echo '<p>Check back soon for amazing deals and special offers!</p>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
              }
              ?>
            </div>
          </div>
        </div>

      </div>

    </section>