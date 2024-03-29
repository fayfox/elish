<?php

namespace elish\helpers;

class CommentHelper
{
    public static function formatToOneLine(string $comment): string
    {
        $comment = str_replace(["\r\n", "\n"], ' - ', trim($comment));
        return str_replace('"', '\"', $comment);
    }

    public static function formatToMultiline(string $comment, int $indent = 4): string
    {
        $lines = explode("\n", str_replace("\r\n", "\n", $comment));
        if (count($lines) == 1) {
            // 只有一行
            return $lines[0];
        }

        $formatComment = $lines[0];
        for ($i = 1; $i < count($lines); $i++) {
            $formatComment .= "\n" . str_repeat(' ', $indent) . ' * ' . $lines[$i];
        }

        return $formatComment;
    }
}
