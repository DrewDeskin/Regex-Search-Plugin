<?php
/*
Plugin Name: Advanced Regex Manager
Description: A plugin that allows users to search, modify, and fix common issues using regex patterns in the WordPress admin area.
Version: 1.0
Author: Drew
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Initialize the plugin
add_action('admin_menu', 'arm_plugin_menu');

function arm_plugin_menu() {
    add_menu_page(
        'Advanced Regex Manager',
        'Regex Manager',
        'manage_options',
        'advanced-regex-manager',
        'arm_dashboard_page',
        'dashicons-search',
        6
    );
}


function arm_dashboard_page() {
    // Start output buffering
    ob_start();

    ?>
    <div class="wrap">
        <h1>Advanced Regex Manager</h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=advanced-regex-manager&tab=search_modify" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] == 'search_modify' ? 'nav-tab-active' : ''; ?>">Search and Modify</a>
        </h2>
        <?php
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'search_modify';
        switch ($active_tab) {
            case 'search_modify':
            default:
                arm_search_modify_tab();
                break;
        }
        ?>
    </div>
    <style>
        .nav-tab-active {
            background-color: #fff;
            border-bottom: 1px solid #fff;
            font-weight: bold;
        }
        table.widefat.fixed {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table.widefat.fixed th, table.widefat.fixed td {
            padding: 8px;
            border: 1px solid #ccc;
        }
        .saved-operations .operation {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
            background-color: #f9f9f9;
        }
    </style>
    <?php
    // Flush the output buffer
    ob_end_flush();
}


// Search Tab
function arm_search_tab() {
    ?>
    <h2>Search for Regex Patterns</h2>
    <form method="post" action="">
        <!-- Form for searching regex patterns -->
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="search_regex">Regex Patterns (comma-separated)</label></th>
                <td><textarea id="search_regex" name="search_regex" class="large-text code" rows="3" required></textarea></td>
            </tr>
        </table>
        <?php submit_button('Search'); ?>
    </form>
    <?php
    if (isset($_POST['search_regex'])) {
        $user_regexes = array_map('trim', array_map('stripslashes', explode(',', $_POST['search_regex'])));
        arm_search_results($user_regexes);
    }
}

// Search and Modify Tab
function arm_modify_tab() {
    ?>
    <h2>Search and Modify Regex Patterns</h2>
    <form method="post" action="">
        <!-- Form for searching and modifying regex patterns -->
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="modify_regex">Regex Patterns (comma-separated)</label></th>
                <td><textarea id="modify_regex" name="modify_regex" class="large-text code" rows="3" required></textarea></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="modify_replacement">Replacement Texts (comma-separated)</label></th>
                <td><textarea id="modify_replacement" name="modify_replacement" class="large-text code" rows="3"></textarea></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="modify_position">Insert Position (optional)</label></th>
                <td>
                    <select id="modify_position" name="modify_position">
                        <option value="">None</option>
                        <option value="before">Before</option>
                        <option value="after">After</option>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="do_modify">Perform Modification</label></th>
                <td><input type="checkbox" id="do_modify" name="do_modify" value="1" /></td>
            </tr>
        </table>
        <?php submit_button('Search and Modify'); ?>
    </form>
    <?php
    if (isset($_POST['modify_regex'])) {
        $user_regexes = array_map('trim', array_map('stripslashes', explode(',', $_POST['modify_regex'])));
        $replacement_texts = isset($_POST['modify_replacement']) ? array_map('stripslashes', explode(',', $_POST['modify_replacement'])) : [];
        $do_replace = isset($_POST['do_modify']) ? true : false;
        $insert_position = isset($_POST['modify_position']) ? $_POST['modify_position'] : '';
        arm_modify_results($user_regexes, $replacement_texts, $do_replace, $insert_position);
    }
}

// Fix Common Issues Tab
function arm_fix_tab() {
    ?>
    <h2>Fix Common Issues</h2>
    <form method="post" action="">
        <!-- Form for fixing common issues using regex patterns -->
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Select Issues to Fix</th>
                <td>
                    <!-- Checkbox for adding a space before <br> tags -->
                    <label><input type="checkbox" name="common_issues[]" value="space_before_br"> Add space before &lt;br&gt; tags</label><br>
                    <!-- Checkbox for removing all instances of &nbsp; -->
                    <label><input type="checkbox" name="common_issues[]" value="remove_nbsp"> Remove all instances of &amp;nbsp;</label><br>
                    <!-- Add more checkboxes as needed -->
                </td>
            </tr>
        </table>
        <?php submit_button('Fix Issues'); ?>
    </form>
    <?php
    if (isset($_POST['common_issues'])) {
        $selected_issues = $_POST['common_issues'];
        arm_fix_common_issues($selected_issues); // Call to the new function
    }
}

function arm_fix_common_issues($selected_issues) {
    global $wpdb;

    $posts = $wpdb->get_results("
        SELECT ID, post_title, post_content
        FROM $wpdb->posts
        WHERE post_status = 'publish'
        AND (post_type = 'post' OR post_type = 'page')
    ");

    echo '<h2>Fix Common Issues Results</h2>';

    $total_fixed = 0;

    foreach ($posts as $post) {
        $post_content = $post->post_content;
        $modified_content = $post_content;

        foreach ($selected_issues as $issue) {
            switch ($issue) {
                case 'space_before_br':
                    // Add space before <br> tags
                    $pattern = '/(?<!\s)<br\s*\/?>/i';
                    $replacement = ' <br>';
                    $modified_content = preg_replace($pattern, $replacement, $modified_content);
                    break;
                case 'remove_nbsp':
                    // Remove all instances of &nbsp;
                    $pattern = '/&nbsp;/i';
                    $replacement = '';
                    $modified_content = preg_replace($pattern, $replacement, $modified_content);
                    break;
            }
        }

        if ($modified_content !== $post_content) {
            $total_fixed++;
            // Update the post content in the database
            wp_update_post(array(
                'ID' => $post->ID,
                'post_content' => $modified_content,
            ));
        }
    }

    echo "<p>Total Instances Fixed: $total_fixed</p>";

    echo '<table class="widefat fixed">';
    echo '<thead><tr><th>Post</th><th>Updated Content</th></tr></thead>';
    echo '<tbody>';

    foreach ($posts as $post) {
        $post_content = $post->post_content;

        foreach ($selected_issues as $issue) {
            switch ($issue) {
                case 'space_before_br':
                    // Add space before <br> tags
                    $pattern = '/(?<!\s)<br\s*\/?>/i';
                    $replacement = ' <br>';
                    $modified_content = preg_replace($pattern, $replacement, $modified_content);
                    break;
                case 'remove_nbsp':
                    // Remove all instances of &nbsp;
                    $pattern = '/&nbsp;/i';
                    $replacement = '';
                    $modified_content = preg_replace($pattern, $replacement, $modified_content);
                    break;
            }
        }

        if ($modified_content !== $post_content) {
            echo '<tr>';
            echo '<td><a href="' . get_permalink($post->ID) . '">' . $post->post_title . '</a></td>';
            echo '<td><pre>' . htmlspecialchars($modified_content) . '</pre></td>';
            echo '</tr>';
        }
    }

    echo '</tbody>';
    echo '</table>';
}

function arm_search_modify_tab() {
    ?>
    <h2>Search and Modify Regex Patterns</h2>
    <form method="post" action="">
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="search_regex">Regex Patterns (comma-separated)</label></th>
                <td><textarea id="search_regex" name="search_regex" class="large-text code" rows="3" required></textarea></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="modify_replacement">Replacement Texts (comma-separated, optional)</label></th>
                <td><textarea id="modify_replacement" name="modify_replacement" class="large-text code" rows="3"></textarea></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="modify_position">Insert Position (optional)</label></th>
                <td>
                    <select id="modify_position" name="modify_position">
                        <option value="">None</option>
                        <option value="before">Before</option>
                        <option value="after">After</option>
                    </select>
                </td>
            </tr>
        </table>
        <?php submit_button('Execute'); ?>
    </form>
    <?php
    if (isset($_POST['search_regex'])) {
        $user_regexes = array_map('trim', array_map('stripslashes', explode(',', $_POST['search_regex'])));
        $replacement_texts = isset($_POST['modify_replacement']) ? array_map('stripslashes', explode(',', $_POST['modify_replacement'])) : [];
        $insert_position = isset($_POST['modify_position']) ? $_POST['modify_position'] : '';

        arm_modify_results($user_regexes, $replacement_texts, true, $insert_position);
    }
}



// Helper Functions
// Add additional helper functions as needed

function arm_modify_results($user_regexes, $replacement_texts, $do_replace, $insert_position) {
    global $wpdb;

    echo '<p>Regex Patterns: ' . htmlspecialchars(implode(', ', $user_regexes)) . '</p>';

    if ($do_replace) {
        echo '<p>Replacement Texts: ' . htmlspecialchars(implode(', ', $replacement_texts)) . '</p>';
    }

    $posts = $wpdb->get_results("
        SELECT ID, post_title, post_content
        FROM $wpdb->posts
        WHERE post_status = 'publish'
        AND (post_type = 'post' OR post_type = 'page')
    ");

    echo '<h2>Search and Modify Results</h2>';

    $total_matches = 0;
    $total_modified = 0;

    foreach ($posts as $post) {
        $post_content = $post->post_content;
        $modified_content = $post_content;

        foreach ($user_regexes as $index => $pattern) {
            $pattern = '/' . trim($pattern) . '/';

            if (@preg_match_all($pattern, $post_content, $matches, PREG_OFFSET_CAPTURE)) {
                if (count($matches[0]) > 0) {
                    $total_matches += count($matches[0]);

                    if ($do_replace) {
                        $replacement = isset($replacement_texts[$index]) ? $replacement_texts[$index] : null;

                        if ($replacement === null || $replacement === '') {
                            // Skip replacement if no replacement text is provided
                            continue;
                        }

                        if ($insert_position === 'before') {
                            $replacement = $replacement . $matches[0][0][0];
                        } elseif ($insert_position === 'after') {
                            $replacement = $matches[0][0][0] . $replacement;
                        }

                        $modified_content = preg_replace($pattern, $replacement, $modified_content);
                        $total_modified++;
                    }
                }
            }
        }

        if ($do_replace && $modified_content !== $post_content) {
            // Update the post content in the database
            wp_update_post(array(
                'ID' => $post->ID,
                'post_content' => $modified_content,
            ));
        }
    }

    echo "<p>Total Matches Found: $total_matches</p>";
    if ($do_replace) {
        echo "<p>Total Instances Modified: $total_modified</p>";
    }

    echo '<table class="widefat fixed">';
    echo '<thead><tr><th>Post</th><th>Matched</th><th>Context</th></tr></thead>';
    echo '<tbody>';

    foreach ($posts as $post) {
        $post_content = $post->post_content;

        foreach ($user_regexes as $index => $pattern) {
            $pattern = '/' . trim($pattern) . '/';

            if (@preg_match_all($pattern, $post_content, $matches, PREG_OFFSET_CAPTURE)) {
                if (count($matches[0]) > 0) {
                    foreach ($matches[0] as $match) {
                        $matched_text = $match[0];
                        $start_pos = $match[1];
                        $context_start = max($start_pos - 30, 0);
                        $context_length = strlen($matched_text) + 60;
                        $context = substr($post_content, $context_start, $context_length);
                        echo '<tr>';
                        echo '<td><a href="' . get_permalink($post->ID) . '">' . $post->post_title . '</a></td>';
                        echo '<td>' . htmlspecialchars($matched_text) . '</td>';
                        echo '<td>' . htmlspecialchars($context) . '</td>';
                        echo '</tr>';
                    }
                }
            }
        }
    }

    echo '</tbody>';
    echo '</table>';
}

function arm_search_results($user_regexes) {
    global $wpdb;

    echo '<p>Regex Patterns: ' . htmlspecialchars(implode(', ', $user_regexes)) . '</p>';

    $posts = $wpdb->get_results("
        SELECT ID, post_title, post_content
        FROM $wpdb->posts
        WHERE post_status = 'publish'
        AND (post_type = 'post' OR post_type = 'page')
    ");

    echo '<h2>Search Results</h2>';

    $total_matches = 0;

    foreach ($posts as $post) {
        $post_content = $post->post_content;

        foreach ($user_regexes as $pattern) {
            $pattern = '/' . trim($pattern) . '/';

            if (@preg_match_all($pattern, $post_content, $matches, PREG_OFFSET_CAPTURE)) {
                if (count($matches[0]) > 0) {
                    $total_matches += count($matches[0]);
                }
            }
        }
    }

    if ($total_matches > 0) {
        echo "<p>Total Matches Found: $total_matches</p>";
    } else {
        echo '<p>No posts or pages found matching the regex patterns.</p>';
    }

    echo '<table class="widefat fixed">';
    echo '<thead><tr><th>Post</th><th>Matched</th><th>Context</th></tr></thead>';
    echo '<tbody>';

    foreach ($posts as $post) {
        $post_content = $post->post_content;

        foreach ($user_regexes as $pattern) {
            $pattern = '/' . trim($pattern) . '/';

            if (@preg_match_all($pattern, $post_content, $matches, PREG_OFFSET_CAPTURE)) {
                if (count($matches[0]) > 0) {
                    foreach ($matches[0] as $match) {
                        $matched_text = $match[0];
                        $start_pos = $match[1];
                        $context_start = max($start_pos - 30, 0);
                        $context_length = strlen($matched_text) + 60;
                        $context = substr($post_content, $context_start, $context_length);
                        echo '<tr>';
                        echo '<td><a href="' . get_permalink($post->ID) . '">' . $post->post_title . '</a></td>';
                        echo '<td>' . htmlspecialchars($matched_text) . '</td>';
                        echo '<td>' . htmlspecialchars($context) . '</td>';
                        echo '</tr>';
                    }
                }
            }
        }
    }

    echo '</tbody>';
    echo '</table>';
}

function arm_saved_tab() {
    ?>
    <h2>Saved Regex Operations</h2>
    <form method="post" action="">
        <!-- Form for adding new saved regex operations -->
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="saved_regex_name">Operation Name</label></th>
                <td><input type="text" id="saved_regex_name" name="saved_regex_name" class="large-text" required></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="saved_regex_pattern">Regex Pattern</label></th>
                <td><textarea id="saved_regex_pattern" name="saved_regex_pattern" class="large-text code" rows="3" required></textarea></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="saved_regex_replacement">Replacement Text (optional)</label></th>
                <td><textarea id="saved_regex_replacement" name="saved_regex_replacement" class="large-text code" rows="3"></textarea></td>
            </tr>
        </table>
        <?php submit_button('Save Operation'); ?>
    </form>
    <?php
    if (isset($_POST['saved_regex_name']) && isset($_POST['saved_regex_pattern'])) {
        $saved_name = sanitize_text_field($_POST['saved_regex_name']);
        $saved_pattern = stripslashes($_POST['saved_regex_pattern']);
        $saved_replacement = isset($_POST['saved_regex_replacement']) ? stripslashes($_POST['saved_regex_replacement']) : '';

        arm_save_regex_operation($saved_name, $saved_pattern, $saved_replacement);
    }

    arm_display_saved_operations();
    arm_add_import_button();
}


?>
