<?php

/**
 * Quote Requests List Page
 * ✅ UPDATED: Added ID column back
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

get_header();

$current_user = wp_get_current_user();
$user_id = get_current_user_id();
$blog_id = get_current_blog_id();

// Get all quotes
$all_quotes = OC_Quotes_CRUD::get_all_quotes();

// Filter quotes for current user
$user_quotes = array_filter($all_quotes, function ($quote) use ($user_id) {
    return intval($quote->user_id) === intval($user_id);
});

$total_quotes = count($user_quotes);

$hero_data = array(
    'background_image' => get_template_directory_uri() . '/assets/images/bg1.jpg',
    'shape_image'      => get_template_directory_uri() . '/assets/images/shape8.png',
);
?>

<!-- BreadCrumb Starts -->
<section class="breadcrumb-main pb-20 pt-14" style="background-image:url(<?php echo esc_url($hero_data['background_image']); ?>);">
    <?php if (!empty($hero_data['shape_image'])): ?>
        <div class="section-shape section-shape1 top-inherit bottom-0" style="background-image: url(<?php echo esc_url($hero_data['shape_image']); ?>);"></div>
    <?php endif; ?>
    <div class="breadcrumb-outer">
        <div class="container">
            <div class="breadcrumb-content text-center">
                <h1 class="mb-3">My Quote Requests</h1>
                <nav aria-label="breadcrumb" class="d-block">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/my-account')); ?>">My Account</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Quote Requests</li>
                    </ul>
                </nav>

                <!-- User Info -->
                <div class="user-breadcrumb-info mt-3">
                    <h6 class="mb-1 text-white">
                        <i class="fas fa-user me-2"></i>
                        <?php echo esc_html($current_user->display_name ?: $current_user->user_login); ?>
                    </h6>
                    <h6 class="opacity-75 text-white">
                        <i class="fas fa-file-alt me-2"></i>
                        <?php echo intval($total_quotes); ?> Quote Request<?php echo $total_quotes !== 1 ? 's' : ''; ?>
                    </h6>
                </div>
            </div>
        </div>
    </div>
    <div class="dot-overlay"></div>
</section>
<!-- BreadCrumb Ends -->

<!-- Quote Requests Section Starts -->
<section class="quotes-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-theme">
                        <h4 class="mb-0 text-white">
                            <i class="fas fa-file-alt me-2"></i>
                            Your Quote Requests
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($user_quotes)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover custom-quotes-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Destination</th>
                                            <th>School</th>
                                            <th>Educator</th>
                                            <th>Email</th>
                                            <th>Special Requests</th>
                                            <th>Meal Quote</th>
                                            <th>Transportation</th>
                                            <th>Requested On</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($user_quotes as $quote):
                                            $destination = !empty($quote->destination_name) ? $quote->destination_name : '--';
                                            $school = !empty($quote->school_name) ? $quote->school_name : '--';
                                            $educator = !empty($quote->educator_name) ? $quote->educator_name : '--';
                                            $email = !empty($quote->email) ? $quote->email : '--';
                                            $special = !empty($quote->special_requests) ? substr($quote->special_requests, 0, 30) . '...' : '--';
                                            $meal = $quote->meal_quote ? '✓ Yes' : '✗ No';
                                            $transport = $quote->transportation_quote ? '✓ Yes' : '✗ No';
                                            $created = !empty($quote->created_at) ? date('M j, Y', strtotime($quote->created_at)) : '--';
                                        ?>
                                            <tr>
                                                <td><strong>#<?php echo intval($quote->id); ?></strong></td>
                                                <td><?php echo esc_html($destination); ?></td>
                                                <td><?php echo esc_html($school); ?></td>
                                                <td><?php echo esc_html($educator); ?></td>
                                                <td><small><?php echo esc_html($email); ?></small></td>
                                                <td><small><?php echo esc_html($special); ?></small></td>
                                                <td><small><?php echo esc_html($meal); ?></small></td>
                                                <td><small><?php echo esc_html($transport); ?></small></td>
                                                <td><?php echo esc_html($created); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">No Quote Requests Found</h5>
                                <p class="text-muted">You haven't submitted any quote requests yet.</p>

                                <a href="<?php echo esc_url(home_url('/request-a-quote')); ?>" class="btn btn-sw bg-theme text-white">
                                    <i class="fas fa-paper-plane me-2"></i>Request a Quote
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="<?php echo esc_url(home_url('/request-a-quote')); ?>" class="btn btn-sw bg-theme text-white me-2">
                        <i class="fas fa-plus me-2"></i>New Quote Request
                    </a>
                    <a href="<?php echo esc_url(home_url('/my-account')); ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to My Account
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Quote Requests Section Ends -->

<style>
    .custom-quotes-table {
        font-size: 0.9rem;
    }

    .custom-quotes-table th {
        white-space: nowrap;
        font-weight: 600;
        background: #f8f9fa;
    }

    .custom-quotes-table td {
        vertical-align: middle;
    }

    .custom-quotes-table tbody tr:hover {
        background-color: #f8f9fa !important;
    }

    @media (max-width: 768px) {
        .custom-quotes-table {
            font-size: 0.8rem;
        }

        .custom-quotes-table th:nth-child(6),
        .custom-quotes-table td:nth-child(6) {
            display: none;
        }
    }
</style>

<?php get_footer(); ?>