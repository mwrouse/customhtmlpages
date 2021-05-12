# Thirty Bees Custom HTML Pages
ThirtyBees module that allows you to take control over your shop.

Create custom HTML pages with custom URLs.

It's like the CMS module, but better since you are not restricted to the template defined by the theme.

You can even use Smarty on the pages!

## `$page` Object
In your custom pages you will have access to the `$page` object which will give you information about the page and child pages.

You have the following properties
- `$page->id`
- `$page->name`
- `$page->meta_title`
- `$page->meta_description`
- `$page->meta_keywords`
- `$page->parent`
- `$page->children[]` (more on this later)
- `$page->related[]` (more on this later)
- `$page->url` (url path)
- `$page->link` (full url including store domain)

## Children Pages (`$page->children[]`)
When editing a page you have the option to set a parent page. This will make the page show up on `$page->children[]` *on the parent page* and will set `$page->parent` on the page you are editing (the *child page*).

Doing this will change the URL to the child page to include the URL of the parent.
> *Example:*
>
> Page A has the URL `foo` and Page B has the URL `bar`.
>
> If you make Page A the parent of Page B then the URL to Page B will be
> `foo/bar`


## Related Pages (`$page->related[]`)
When editing a page you will have the option to set what pages the current page is related to.

This is labeled as (`Show On` in the edit page).

**THIS IS NOT SETTING WHAT PAGES ARE RELATED TO THE CURRENT PAGE**

It gives you the ability to set a one-to-many relationship by editing the *one* page, not the *many* pages.

> *Example:*
>
> If you mark Page B to be *shown on* Page A then when you are looking at Page A then `$page->related[]` will contain Page B.

> **NOTE:**
>
> Making this relationship does not effect `$page->children[]` or `$page->parent` on either of the pages.


## Adding Categories (`$category` or `$categories[]`)
On the edit page you have the ability to add one or more categories to the current page.

If you add one category, then the `$category` variable will be populated.

If you add more than one, then the `$categories[]` array variable will be populated.

These are full category classes and will have a list of products under `$category->products` or `$categories[]->products`.

> *Alternative:*
>
> You can alternatively (and dynamically) add a single category to any page by adding the `id_category` parameter to the URL. This will populate the `$category` variable.


## Adding Products (`$product` or `$products[]`)
On the edit page you have the ability to add one or more products to the current page.

If you add one product, then the `$product` variable will be populated.

If you add more than one, then the `$products[]` array variable will be populated.

These are full product classes.

> *Alternative:*
>
> You can alternatively (and dynamically) add a single product to any page by adding the `id_product` parameter to the URL. This will populate the `$product` variable.


## Breadcrumbs
This module will automatically create breadcrumbs whenever you are on a page.


## Sitemap
If you use your `sitemap.tpl` file (`/sitemap`) you can add your custom pages to it by modifying the `sitemap.tpl` file for your theme and adding the following hook:
```
{hook h='displaySitemapPages'}
```

Add that hook to the *Pages** section of the `sitemap.tpl` file. Doing this will automatically add the trees for your pages.

## Routes (and the 404 Page)
This module generates routes based on your active pages. Only routes for pages will be generated.

Since this module currently doesn't support rewrites in the page URLs, then there is no ambiguity in the routes. Therefore if you do not type a route correctly then you will get the 404 page!

All generated routes have the ability to include an option `/` at the end. (so `foo/` and `foo` will go to the same page).