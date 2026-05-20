<?php

namespace App\Helpers;

class MarkdownHelper
{
    /**
     * Convertit un texte Markdown simple en HTML sécurisé.
     * Gère : **gras**, *italique*, listes à tirets, sauts de ligne.
     */
    public static function toHtml(string $text): string
    {
        // Échapper d'abord le HTML pour sécurité
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // **gras**
        $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);

        // *italique* (mais pas les ** déjà traités)
        $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $text);

        // _italique_
        $text = preg_replace('/_(.+?)_/s', '<em>$1</em>', $text);

        // Listes à tirets (- élément ou • élément)
        $lines = explode("\n", $text);
        $inList = false;
        $result = [];

        foreach ($lines as $line) {
            $trimmed = ltrim($line);
            if (preg_match('/^[-•]\s+(.+)/', $trimmed, $m)) {
                if (!$inList) {
                    $result[] = '<ul class="list-disc pl-4 my-1 space-y-0.5">';
                    $inList = true;
                }
                $result[] = '<li class="text-sm">' . trim($m[1]) . '</li>';
            } else {
                if ($inList) {
                    $result[] = '</ul>';
                    $inList = false;
                }
                // Saut de ligne pour les lignes vides
                if (trim($trimmed) === '') {
                    $result[] = '<br>';
                } else {
                    $result[] = $line;
                }
            }
        }

        if ($inList) {
            $result[] = '</ul>';
        }

        $text = implode("\n", $result);

        // Convertir les \n restants en <br>
        $text = nl2br($text);

        // Nettoyer les <br> doubles inutiles
        $text = preg_replace('/(<br\s*\/?>\s*){3,}/', '<br><br>', $text);

        return $text;
    }
}
