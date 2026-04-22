<?php
$pageTitle = 'Testimonials';
$pageDesc = 'Read what our customers say about Travelr Taxi & Tours Services. Real reviews from real riders.';
require_once 'includes/header.php';

$testimonials = dbFetchAll("SELECT * FROM testimonials WHERE is_approved = 1 ORDER BY created_at DESC");
?>

<section class="page-hero">
    <div class="container">
        <h1>Customer Reviews</h1>
        <p>Real feedback from real riders</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="testimonials-grid">
            <?php if (empty($testimonials)): ?>
                <p class="text-center">No reviews yet. Be the first to share your experience!</p>
            <?php else: ?>
                <?php foreach ($testimonials as $t): ?>
                <div class="testimonial-card-full">
                    <div class="testimonial-stars">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <i class="fas fa-star<?php echo $i < $t['rating'] ? '' : ' empty'; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <p class="testimonial-text">"<?php echo sanitize($t['message']); ?>"</p>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar"><i class="fas fa-user-circle"></i></div>
                        <div>
                            <strong><?php echo sanitize($t['customer_name']); ?></strong>
                            <span><?php echo sanitize($t['location']); ?></span>
                        </div>
                    </div>
                    <div class="testimonial-date"><?php echo formatDate($t['created_at']); ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Submit Review -->
        <div class="review-form-section">
            <h2>Share Your Experience</h2>
            <form action="/submit-review.php" method="POST" class="review-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                <div class="form-row">
                    <div class="form-group half">
                        <label>Your Name *</label>
                        <input type="text" name="customer_name" required>
                    </div>
                    <div class="form-group half">
                        <label>Location</label>
                        <input type="text" name="location" placeholder="e.g., Kingston">
                    </div>
                </div>
                <div class="form-group">
                    <label>Rating *</label>
                    <div class="star-rating" id="starRating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" <?php echo $i === 5 ? 'checked' : ''; ?>>
                        <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label>Your Review *</label>
                    <textarea name="message" rows="4" required placeholder="Tell us about your experience..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit Review</button>
            </form>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
