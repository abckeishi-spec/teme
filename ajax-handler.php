<?php
/**
 * 独立したAJAXハンドラー - WordPressに依存しない
 * admin-ajax.phpが使用できない場合のフォールバック
 */

// WordPressの読み込み
define('WP_USE_THEMES', false);
require_once(__DIR__ . '/wp-load.php');

// CORS対応
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// OPTIONSリクエストの処理
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// POSTリクエストのみ受け付け
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// エラーハンドリング
set_error_handler(function($severity, $message, $file, $line) {
    error_log("AJAX Handler Error: $message in $file on line $line");
});

try {
    // アクション取得
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    error_log('[AJAX_HANDLER] Action: ' . $action);
    error_log('[AJAX_HANDLER] POST data: ' . print_r($_POST, true));
    
    switch ($action) {
        case 'test_connection':
            // 接続テスト
            echo json_encode([
                'success' => true,
                'message' => '独立AJAXハンドラー接続成功',
                'timestamp' => time(),
                'server_info' => [
                    'php_version' => PHP_VERSION,
                    'wordpress_loaded' => defined('ABSPATH'),
                    'post_data_received' => !empty($_POST)
                ]
            ]);
            break;
            
        case 'search_grants':
            // 助成金検索
            $search = sanitize_text_field($_POST['search'] ?? '');
            $page = max(1, intval($_POST['page'] ?? 1));
            $per_page = 6;
            
            $args = [
                'post_type' => 'grant',
                'posts_per_page' => $per_page,
                'paged' => $page,
                'post_status' => 'publish'
            ];
            
            if (!empty($search)) {
                $args['s'] = $search;
            }
            
            $query = new WP_Query($args);
            $grants = [];
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $grants[] = [
                        'id' => get_the_ID(),
                        'title' => get_the_title(),
                        'excerpt' => get_the_excerpt(),
                        'permalink' => get_the_permalink(),
                        'date' => get_the_date('Y-m-d'),
                        'meta' => [
                            'max_amount' => get_post_meta(get_the_ID(), 'max_amount', true),
                            'deadline_date' => get_post_meta(get_the_ID(), 'deadline_date', true),
                            'organization' => get_post_meta(get_the_ID(), 'organization', true),
                            'application_status' => get_post_meta(get_the_ID(), 'application_status', true)
                        ]
                    ];
                }
                wp_reset_postdata();
            }
            
            echo json_encode([
                'success' => true,
                'message' => '検索成功',
                'data' => [
                    'grants' => $grants,
                    'total' => $query->found_posts,
                    'pages' => $query->max_num_pages,
                    'current_page' => $page,
                    'per_page' => $per_page,
                    'search_query' => $search
                ]
            ]);
            break;
            
        case 'check_database':
            // データベース状況確認
            $all_posts = get_posts([
                'post_type' => 'grant',
                'posts_per_page' => -1,
                'post_status' => 'any'
            ]);
            
            $published_posts = get_posts([
                'post_type' => 'grant',
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ]);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'total_posts' => count($all_posts),
                    'published_posts' => count($published_posts),
                    'sample_posts' => array_slice(array_map(function($post) {
                        return [
                            'id' => $post->ID,
                            'title' => $post->post_title,
                            'status' => $post->post_status
                        ];
                    }, $all_posts), 0, 10),
                    'wordpress_loaded' => defined('ABSPATH'),
                    'post_type_exists' => post_type_exists('grant')
                ]
            ]);
            break;
            
        case 'force_create_data':
            // サンプルデータ強制作成
            if (function_exists('gi_insert_sample_grants_with_prefectures')) {
                gi_insert_sample_grants_with_prefectures();
                
                $new_posts = get_posts([
                    'post_type' => 'grant',
                    'posts_per_page' => -1,
                    'post_status' => 'publish'
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Sample data created via independent handler',
                    'created_count' => count($new_posts)
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Sample data creation function not found'
                ]);
            }
            break;
            
        case 'get_filters':
            // フィルターオプション取得
            $categories = get_terms(['taxonomy' => 'grant_category', 'hide_empty' => false]);
            $prefectures = get_terms(['taxonomy' => 'grant_prefecture', 'hide_empty' => false]);
            $industries = get_terms(['taxonomy' => 'grant_industry', 'hide_empty' => false]);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'categories' => array_map(function($term) {
                        return ['id' => $term->term_id, 'name' => $term->name, 'slug' => $term->slug];
                    }, $categories ?: []),
                    'prefectures' => array_map(function($term) {
                        return ['id' => $term->term_id, 'name' => $term->name, 'slug' => $term->slug];
                    }, $prefectures ?: []),
                    'industries' => array_map(function($term) {
                        return ['id' => $term->term_id, 'name' => $term->name, 'slug' => $term->slug];
                    }, $industries ?: [])
                ]
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => '不明なアクション: ' . $action
            ]);
    }
    
} catch (Exception $e) {
    error_log('[AJAX_HANDLER] Exception: ' . $e->getMessage());
    error_log('[AJAX_HANDLER] Trace: ' . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'サーバーエラー: ' . $e->getMessage(),
        'error_details' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'code' => $e->getCode()
        ]
    ]);
}

exit;