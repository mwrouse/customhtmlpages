<?php


class CustomHTMLPageModel extends ObjectModel
{
    public $id;
    public $name;

    public $meta_title;
    public $meta_description;
    public $meta_keywords;
    public $content;

    public $active = 1;

    public $parent = null; // Reference
    public $children = []; // Array of references

    public $link_rewrite;

    public $url;

    private $raw;


    public function __construct($raw)
    {
        $this->id = $raw['id_page'];
        $this->name = $raw['name'];
        $this->meta_title = $raw['meta_title'];
        $this->meta_description = $raw['meta_description'];
        $this->meta_keywords = $raw['meta_keywords'];
        $this->content = $raw['content'];
        $this->active = $raw['active'];
        $this->link_rewrite = $raw['url'];
        $this->url = $raw['url'];

        $this->raw = $raw;
    }


    /**
     * Checks if the page has children
     */
    public function hasChildren()
    {
        return count($this->children) > 0;
    }


    /**
     * Checks if the page has a parent
     */
    public function hasParent()
    {
        return !is_null($this->parent);
    }


    /**
     * Adds a child to the page
     */
    public function addChild($child)
    {
        array_push($this->children, $child);
        $child->parent = $this;
        $child->computeFullURL();
    }


    /**
     * Returns/Recomputes the full URL to this page
     */
    public function computeFullURL($computeChildren = true)
    {
        $prefix = "";

        if (!is_null($this->parent))
            $prefix = $this->parent->computeFullURL(false);

        if (!empty($prefix))
            $prefix .= '/';

        $this->url = $prefix.$this->link_rewrite;

        if ($computeChildren && count($this->children) > 0) {
            foreach ($this->children as $child) {
                $child->computeFullURL();
            }
        }

        return $this->url;
    }
}