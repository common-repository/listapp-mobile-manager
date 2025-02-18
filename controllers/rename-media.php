<?php
/* Original code from: 
 * http://bradt.ca/archives/image-crop-position-in-wordpress/
 *
 * Modified to WordPress Answers:
 * http://wordpress.stackexchange.com/q/51920/12615
 *
 * Check the function bt_image_make_intermediate_size
 * That's where the Thumbnails renaming occurs and all added images must be inserted
 */

/* Example Usage:
 * bt_add_image_size( 'product-screenshot', 300, 300, array( 'left', 'top' ) );
 * bt_add_image_size( 'product-feature', 460, 345, array( 'center', 'top' ) );
 */

add_filter( 'intermediate_image_sizes_advanced', 'bt_intermediate_image_sizes_advanced');
add_filter( 'wp_generate_attachment_metadata', 'bt_generate_attachment_metadata', 10, 2);


/**
 * Run our own resizing functions by hooking into
 * the 'wp_generate_attachment_metadata' filter
 */

function bt_generate_attachment_metadata($metadata, $attachment_id)
{
    $attachment = get_post($attachment_id);

    $uploadPath = wp_upload_dir();
    $file = path_join($uploadPath['basedir'], $metadata['file']);

    if (!preg_match('!^image/!', get_post_mime_type($attachment)) || !file_is_displayable_image($file)) return $metadata;

    global $_wp_additional_image_sizes;

    foreach (get_intermediate_image_sizes() as $s) {
        $sizes[$s] = array('width' => '', 'height' => '', 'crop' => FALSE);
        if (isset($_wp_additional_image_sizes[$s]['width']))
            $sizes[$s]['width'] = intval($_wp_additional_image_sizes[$s]['width']); // For theme-added sizes
        else
            $sizes[$s]['width'] = get_option("{$s}_size_w"); // For default sizes set in options
        if (isset($_wp_additional_image_sizes[$s]['height']))
            $sizes[$s]['height'] = intval($_wp_additional_image_sizes[$s]['height']); // For theme-added sizes
        else
            $sizes[$s]['height'] = get_option("{$s}_size_h"); // For default sizes set in options
        if (isset($_wp_additional_image_sizes[$s]['crop']))
            $sizes[$s]['crop'] = $_wp_additional_image_sizes[$s]['crop'];
        else
            $sizes[$s]['crop'] = get_option("{$s}_crop");
    }

    foreach ($sizes as $size => $size_data) {
        $resized = bt_image_make_intermediate_size($file, $size_data['width'], $size_data['height'], $size, $size_data['crop']);
        if ($resized)
            $metadata['sizes'][$size] = $resized;
    }

    return $metadata;
}


/**
 * Resize an image to make a thumbnail or intermediate size.
 *
 * The returned array has the file size, the image width, and image height. The
 * filter 'image_make_intermediate_size' can be used to hook in and change the
 * values of the returned array. The only parameter is the resized file path.
 *
 * @param string $file File path.
 * @param int $width Image width.
 * @param int $height Image height.
 * @param bool|array $crop Optional, default is false. Whether to crop image to specified height and width or resize. An array can specify positioning of the crop area.
 * @return bool|array False, if no image was created. Metadata array on success.
 */
function bt_image_make_intermediate_size($file, $width, $height, $size, $crop = false)
{
    if ($width || $height) {
        switch ($size) {
            case 'thumbnail':
                $suffix = 'small';
                break;
            case 'medium':
                $suffix = 'medium';
                break;
            case 'large':
                $suffix = 'large';
                break;
            default:
                return false;
        }
        $resized_file = wp_get_image_editor($file);
        if (!is_wp_error($resized_file)) {
            $resized_file->resize($width, $height, $crop);
            $filename = $resized_file->generate_filename($suffix);
            $resized_file->save($filename);
        }
        if (!is_wp_error($resized_file) && $resized_file && $info = getimagesize($filename)) {
            $filename = apply_filters('image_make_intermediate_size', $filename);
            return array(
                'file' => wp_basename($filename),
                'width' => $info[0],
                'height' => $info[1],
            );
        }
    }
    return false;
}



/**
 * Returning no sizes (an empty array) will force
 * wp_generate_attachment_metadata to skip creating intermediate image sizes on
 * upload, then we can run our own resizing functions by hooking into the
 * 'wp_generate_attachment_metadata' filter
 */
function bt_intermediate_image_sizes_advanced( $sizes ) {
	return array();
}