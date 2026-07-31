# Using explayouts_tags_query

## Direct PHP usage

`expLayoutsTagsQuery` exposes a single method, `execute( $params = array() )`, returning `expLayoutsContentBrowserItem[]`:

```php
<?php
$query = new expLayoutsTagsQuery();
$items = $query->execute( array(
    'tags' => array( 'news', 'featured' ),
    'parent_id' => 2,
    'tags_filter_logic' => 'any', // or 'all'
    'sort_type' => 'content_name',
    'sort_direction' => 'asc',
    'limit' => 10,
    'offset' => 0,
) );

foreach ( $items as $item )
{
    echo $item->name . ' (' . $item->classIdentifier . ")\n";
}
?>
```

Tags are lowercased and trimmed before matching, on both sides — matching is case-insensitive and exact per keyword.

## Tag sources

The tag list is collected from up to three sources and merged/deduplicated:

1. `tags` — explicit array (or comma-separated string) in the parameters.
2. `use_tags_from_current_content` + `field_definition_identifier` — read tags from a content object's field(s); the object is resolved from `content_id` / `location_id` (see below). `field_definition_identifier` accepts a comma-separated list of field identifiers.
3. `use_tags_from_query_string` + `query_string_param_name` — read tags from a URL GET parameter (array or comma-separated string).

If the merged tag list is empty, `execute()` returns an empty array without querying.

## Supported datatypes

When reading tags from content fields (and when matching candidate objects):

- `ezkeyword` — via `eZKeyword::keywordArray()` (built-in).
- `eztags` — Netgen Tags, detected by duck typing (`attribute( 'keyword' )` / `getKeyword()`); works when the extension is installed, no hard dependency.
- Anything else — the attribute's `toString()` value split on commas.

## Parameters

| Parameter | Meaning |
|-----------|---------|
| `tags` | Explicit tags (array or CSV string) |
| `parent_id` | Subtree start node (default 2) |
| `depth` | Traversal depth (default 10) |
| `subtree_limit` | Max nodes fetched before filtering (default 1000) |
| `tags_filter_logic` | `any` (default) or `all` |
| `field_definition_identifier` | Field(s) to read tags from, and the field checked on candidates when set |
| `use_tags_from_current_content` | Enable tag source 2 |
| `use_tags_from_query_string` / `query_string_param_name` | Enable tag source 3 |
| `content_id` / `location_id` / `use_current_location` | How the source object for tag source 2 is resolved |
| `only_main_locations` | Skip non-main nodes |
| `content_types` / `content_types_filter` | Include/exclude by class identifier |
| `sort_type` | `date_published` (default), `date_modified`, `content_name`, `location_priority` |
| `sort_direction` | `asc` or `desc` (default `desc`) |
| `limit` / `offset` | Pagination; `0` means unlimited |

Note: `content_types` filtering and sorting currently hit a known gap in the item class — see [TODO.md](TODO.md).

## Scenario: tag cloud landing page driven by the URL

```php
<?php
// /taglisting?tag=fitness,health
$query = new expLayoutsTagsQuery();
$items = $query->execute( array(
    'use_tags_from_query_string' => true,
    'query_string_param_name' => 'tag',
    'parent_id' => 2,
    'limit' => 20,
) );
?>
```

## Scenario: "more like this" based on the viewed article's keywords

```php
<?php
$items = $query->execute( array(
    'use_tags_from_current_content' => true,
    'field_definition_identifier' => 'tags',
    'content_id' => $object->attribute( 'id' ),
    'tags_filter_logic' => 'any',
    'only_main_locations' => true,
    'limit' => 5,
) );
?>
```

## Scenario: strict multi-tag match within a section

```php
<?php
$items = $query->execute( array(
    'tags' => 'video,tutorial',
    'parent_id' => 152,
    'depth' => 3,
    'tags_filter_logic' => 'all',
) );
?>
```

## Scenario: CLI usage

```bash
php bin/php/ezexec.php ai/bin/tmp/test_tags_query.php --allow-root-user
```

## Collection configuration in Exponential Layouts

The layout collection runtime uses the query type identifier registered in `extension/explayouts/settings/explayouts.ini.append.php`:

```ini
[QuerySettings]
AvailableQueries[]=exp_content_tags

[QueryType_exp_content_tags]
Name=Exp tags
Handler=expLayoutsTagsQueryHandler
```

Pick `exp_content_tags` as the query type of a dynamic collection in the layouts admin UI. That handler class ships with the `explayouts` extension; the class in this extension is the standalone port of the upstream package for direct PHP use (custom modules, block handlers, CLI scripts).

## Customization

### Settings layer (INI)

This extension ships only `settings/design.ini.append.php` (a `DesignExtensions[]` registration; no templates yet). The collection query type shown above lives in `explayouts.ini`, so integrators can adjust it from outside through the standard cascade, lowest to highest priority:

1. `settings/*.ini` — kernel defaults
2. `extension/<ext>/settings/*.ini.append.php` — extension defaults (`extension/explayouts/settings/explayouts.ini.append.php` defines the query type)
3. `settings/siteaccess/<siteaccess>/*.ini.append.php` — siteaccess overrides
4. `extension/<ext>/settings/siteaccess/<siteaccess>/*.ini.append.php` — extension siteaccess overrides
5. `settings/override/*.ini.append.php` — global overrides (always win)

For example, `settings/override/explayouts.ini.append.php` can rename the query type or swap the handler:

```ini
[QueryType_exp_content_tags]
Name=Tagged content
Handler=myTagsQueryHandler
```

### Template layer (design overrides)

No templates are shipped. Collection items returned by this query are rendered by the block templates of your layouts design; override those in your design extension, not here.

### PHP layer (extension points)

`expLayoutsTagsQuery` keeps every stage in `protected` methods, so subclasses can replace individual steps:

- `collectTags()` — add another tag source.
- `attributeTags()` — support another tag datatype.
- `objectMatchesTags()` — change matching semantics (e.g. prefix matching).
- `resolveContent()`, `filterByContentType()`, `sort()`, `applyLimitOffset()` — same override pattern as the relation list queries.

For collection-driven customization, implement your own handler class (following `expLayoutsTagsQueryHandler` in `explayouts`) and register it via the INI override shown above.
