# explayouts_tags_query

Tags collection query handler for Exponential Layouts on Exponential Legacy / Exponential 6. It scans a content subtree and returns items whose keyword/tag fields match a set of tags, with `any`/`all` matching logic, content-class filtering, sorting and pagination — the tag-driven data source pattern used by layout collection blocks.

Exponential Legacy port inspired by `netgen-layouts/layouts-ibexa-tags-query`.

## Key classes

| Class | File | Purpose |
|-------|------|---------|
| `expLayoutsTagsQuery` | `classes/explayoutstagsquery.php` | Finds subtree items matching tags from parameters, a content field or the query string |

## Quick example

```php
<?php
$query = new expLayoutsTagsQuery();
$items = $query->execute( array(
    'tags' => array( 'news', 'featured' ),
    'parent_id' => 2,
    'tags_filter_logic' => 'any',
    'limit' => 10,
) );

foreach ( $items as $item )
{
    echo $item->name . "\n"; // expLayoutsContentBrowserItem public property
}
?>
```

Supported tag datatypes: `ezkeyword` (built-in), `eztags` (when the Netgen Tags extension is installed) and any string/CSV attribute as a fallback.

## Collection configuration

The Exponential Layouts collection runtime registers the tags query type `exp_content_tags` in `extension/explayouts/settings/explayouts.ini.append.php`; see [doc/USAGE.md](doc/USAGE.md) for how the two layers relate.

## Documentation

- [INSTALL.md](INSTALL.md) — activation steps and dependencies
- [doc/USAGE.md](doc/USAGE.md) — parameters, tag sources, scenarios and customization
- [doc/FAQ.md](doc/FAQ.md) — frequently asked questions
- [doc/TODO.md](doc/TODO.md) — known gaps and planned work
- [doc/SUPPORT.md](doc/SUPPORT.md) — how to get help
