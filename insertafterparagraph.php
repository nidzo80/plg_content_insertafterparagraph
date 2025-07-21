<?php
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

class PlgContentInsertAfterParagraph extends CMSPlugin
{
    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
    }

    public function onContentPrepare($context, &$article, &$params, $page = 0)
    {
        // Check if we're in the right context (com_content.article)
        if ($context !== 'com_content.article' || !isset($article->text)) {
            return true;
        }

        // Get plugin parameters
        $paragraphNumber = (int) $this->params->get('paragraph_number', 2);
        $contentType = $this->params->get('content_type', 'html');
        $enabledCategories = $this->params->get('enabled_categories', []);

        // Check if the article's category is in the enabled categories (if specified)
        if (!empty($enabledCategories)) {
            $articleCatid = isset($article->catid) ? (int) $article->catid : 0;
            if (!in_array($articleCatid, $enabledCategories)) {
                return true;
            }
        }

        // Split the article text into paragraphs
        $paragraphs = explode('</p>', $article->text);
        $paragraphCount = count(array_filter($paragraphs, function ($p) {
            return trim(strip_tags($p)) !== '';
        }));

        // Ensure the specified paragraph number exists
        if ($paragraphNumber > $paragraphCount) {
            return true;
        }

        // Prepare the content to insert based on type
        $contentToInsert = '';
        switch ($contentType) {
            case 'html':
                $contentToInsert = $this->params->get('html_content', '');
                break;
            case 'image':
                $imagePath = $this->params->get('image_path', '');
                $imageAlt = $this->params->get('image_alt', '');
                if (!empty($imagePath)) {
                    $contentToInsert = '<p><img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($imageAlt) . '" /></p>';
                }
                break;
            case 'text':
                $textContent = $this->params->get('text_content', '');
                if (!empty($textContent)) {
                    $contentToInsert = '<p>' . htmlspecialchars($textContent) . '</p>';
                }
                break;
        }

        if (empty($contentToInsert)) {
            return true;
        }

        // Insert content after the specified paragraph
        $currentParagraph = 0;
        $newText = '';
        foreach ($paragraphs as $index => $paragraph) {
            $newText .= $paragraph;
            if (trim(strip_tags($paragraph)) !== '') {
                $currentParagraph++;
            }
            if ($currentParagraph === $paragraphNumber && $index < count($paragraphs) - 1) {
                $newText .= $contentToInsert;
            }
            if ($index < count($paragraphs) - 1) {
                $newText .= '</p>';
            }
        }

        $article->text = $newText;

        return true;
    }
}
?>