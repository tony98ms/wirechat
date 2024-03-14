<?php

namespace Namu\WireChat\Helpers;


class Helper {

    /**
     * Formats file extensions for use in the 'accept' attribute of an input element.
     *
     * This function takes an array of file extensions (without the leading dot)
     * and formats them with leading dots and comma separators for use in the 'accept'
     * attribute of an HTML input element.
     *
     * @param array $fileExtensions The array of file extensions to format.
     * @return string The formatted string for the 'accept' attribute.
     */
    public static function formattedImageMimesForAcceptAttribute(): string {
        $fileExtensions= config('wirechat.attachments.image_mimes');
        return '.' . implode(',.', $fileExtensions);
    }
}