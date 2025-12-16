<?php
/**
 * Shareable Public View Template
 * 
 * Matches design of Bookings/Schools list pages
 */

if (!defined('ABSPATH')) {
    exit;
}

$item = $args['item'];
$items = json_decode($item->items, true);
if (!is_array($items)) {
    $items = array();
}

// ============================================
// PRESENTATION LAYER
// ============================================

get_header();

// Hero section configuration
$hero_bg = OC_Asset_Handler::get_theme_image('hero-bg');
// Optional: Use theme compatibility if available, otherwise manual fallback is fine as per request to use core logic
?>

<!-- ================================================
     HERO SECTION
     ================================================ -->
<section class="breadcrumb-main" style="background-image:url('<?php echo esc_url($hero_bg); ?>');">
    <div class="breadcrumb-outer">
        <div class="container">
            <div class="breadcrumb-content text-center pt-5 pb-1">
                <h5 class="theme mb-0">Forum Music Festival</h5>
                <h1 class="mb-3 white"><?php echo esc_html($item->title); ?></h1>

                <div class="user-breadcrumb-info">
                    <h6 class="white mb-1">
                        <i class="fas fa-share-alt me-2"></i>
                        <?php _e('Shared Content', 'organization-core'); ?>
                    </h6>
                    <h6 class="white opacity-75">
                        <i class="fas fa-clock me-2"></i>
                        <?php echo date_i18n(get_option('date_format'), strtotime($item->created_at)); ?>
                    </h6>
                </div>
            </div>
        </div>
    </div>
    <div class="bread-overlay"></div>
</section>

<!-- ================================================
     CONTENT SECTION
     ================================================ -->
<section class="shareables-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            
            <?php if (!empty($items)): ?>
                <div class="col-lg-10 col-xl-9">
                    
                    <?php foreach ($items as $content_item): ?>
                        <div class="card shadow-sm mb-4 share-item-card">
                            <div class="card-body p-4">
                                
                                <?php if (!empty($content_item['title'])): ?>
                                    <h4 class="card-title mb-3" style="color: #333; font-weight: 600;">
                                        <?php echo esc_html($content_item['title']); ?>
                                    </h4>
                                <?php endif; ?>

                                <?php if (!empty($content_item['description'])): ?>
                                    <div class="card-text mb-4 text-muted" style="line-height: 1.6;">
                                        <?php echo wpautop(wp_kses_post($content_item['description'])); ?>
                                    </div>
                                <?php endif; ?>

                                <?php 
                                // Media Handling
                                $media_list = isset($content_item['media_details']) ? $content_item['media_details'] : array();
                                // Fallback for old data: just IDs? Logic would be complex, assuming new data has details or we rely on just display logic if needed. 
                                // For now, let's assume media_details or fallback if possible, but simplest is relying on details stored.
                                ?>

                                <?php if (!empty($media_list) && is_array($media_list)): ?>
                                    <div class="media-grid row g-3">
                                        <?php foreach ($media_list as $media): ?>
                                            <div class="col-sm-6 col-md-4">
                                                <div class="media-item border rounded p-2 text-center h-100 d-flex flex-column justify-content-center align-items-center bg-light">
                                                    <?php 
                                                    $url = isset($media['url']) ? $media['url'] : '';
                                                    $type = isset($media['type']) ? $media['type'] : 'unknown';
                                                    
                                                    if (!$url && isset($media['id'])) {
                                                        $url = wp_get_attachment_url($media['id']); // Fallback if URL missing but ID exists
                                                    }
                                                    ?>

                                                    <?php if ($type === 'image'): ?>
                                                        <a href="<?php echo esc_url($url); ?>" target="_blank" class="d-block w-100">
                                                            <img src="<?php echo esc_url($url); ?>" alt="Image" class="img-fluid rounded" style="max-height: 200px; object-fit: cover;">
                                                        </a>
                                                    <?php elseif ($type === 'video'): ?>
                                                        <video controls class="w-100 rounded" style="max-height: 200px;">
                                                            <source src="<?php echo esc_url($url); ?>" type="<?php echo esc_attr($media['mime']); ?>">
                                                            <?php _e('Your browser does not support the video tag.', 'organization-core'); ?>
                                                        </video>
                                                    <?php else: // PDF or other files ?>
                                                        <div class="file-preview py-3">
                                                            <i class="fas fa-file-pdf fa-3x text-danger mb-2"></i>
                                                            <br>
                                                            <a href="<?php echo esc_url($url); ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                                                                <?php _e('Download / View', 'organization-core'); ?>
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted"><?php _e('No content items found.', 'organization-core'); ?></p>
                </div>
            <?php endif; ?>

        </div>
    </div>
</section>

<?php get_footer(); ?>
