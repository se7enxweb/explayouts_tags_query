Exponential Layouts Tags Query
==============================

General description
-------------------

Exponential Layouts Tags Query (`explayouts_tags_query`) provides the tags
collection query handler for Exponential Layouts on Exponential Legacy /
Exponential 6. It scans a content subtree and returns items whose
keyword/tag fields match a set of tags, with `any`/`all` matching logic,
content-class filtering, sorting and pagination — the tag-driven data
source pattern used by layout collection blocks.

This extension is an Exponential Legacy port inspired by
`netgen-layouts/layouts-ibexa-tags-query` and provides the following
capabilities:

- Tag-driven content listing - Use this feature to list all content in a
  subtree that carries one or more given tags.
- Multiple tag sources - Use this feature to collect tags from explicit
  parameters, from a content object's fields, or from a URL query string
  parameter (for tag-cloud landing pages).
- `any`/`all` matching logic - Use this feature to return items matching at
  least one tag, or only items carrying every requested tag.
- Layout collection integration - Use the `exp_content_tags` query type to
  drive dynamic collections from the layouts admin UI.

Features
--------

The following features are provided by the Exponential Layouts Tags Query
extension:

- A single, focused query class, `expLayoutsTagsQuery`, exposing one method,
  `execute( $params = array() )`, returning `expLayoutsContentBrowserItem[]`.
- Three tag sources, merged and deduplicated before matching:
  1. `tags` — an explicit array (or comma-separated string) in the
     parameters.
  2. `use_tags_from_current_content` + `field_definition_identifier` — tags
     read from a content object's field(s); the object is resolved from
     `content_id` / `location_id` / `use_current_location`, and
     `field_definition_identifier` accepts a comma-separated list of field
     identifiers.
  3. `use_tags_from_query_string` + `query_string_param_name` — tags read
     from a URL GET parameter (array or comma-separated string).
  If the merged tag list is empty, `execute()` returns an empty array
  without querying.
- Case-insensitive, exact-per-keyword matching: tags are trimmed and
  lowercased on both sides before comparison — from parameters, content
  fields and the query string alike — so matching is normalized, not
  substring-based.
- Support for multiple tag datatypes when reading tags from content fields
  and when matching candidate objects:
  - `ezkeyword` — via `eZKeyword::keywordArray()` (built-in).
  - `eztags` — Netgen Tags, detected by duck typing
    (`attribute( 'keyword' )` / `getKeyword()`); works when the extension is
    installed, with no hard dependency.
  - Anything else — the attribute's `toString()` value split on commas.
- A rich parameter set: `tags`, `parent_id` (subtree start node, default 2),
  `depth` (default 10), `subtree_limit` (max nodes fetched before filtering,
  default 1000), `tags_filter_logic` (`any` default, or `all`),
  `field_definition_identifier`, `use_tags_from_current_content`,
  `use_tags_from_query_string` / `query_string_param_name`,
  `content_id` / `location_id` / `use_current_location`,
  `only_main_locations` (skip non-main nodes), `content_types` /
  `content_types_filter` (include/exclude by class identifier), `sort_type`
  (`date_published` default, `date_modified`, `content_name`,
  `location_priority`), `sort_direction` (`asc` or `desc`, default `desc`)
  and `limit` / `offset` (pagination; `0` means unlimited).
- Layout collection integration through the query type identifier
  `exp_content_tags`, registered in
  `extension/explayouts/settings/explayouts.ini.append.php` and selectable
  as the query type of a dynamic collection in the layouts admin UI.
- Stage-by-stage `protected` internals — `collectTags()` (add another tag
  source), `attributeTags()` (support another tag datatype),
  `objectMatchesTags()` (change matching semantics, e.g. prefix matching),
  `resolveContent()`, `filterByContentType()`, `sort()`,
  `applyLimitOffset()` — so subclasses can replace individual steps.
- Standalone by design: the class works in custom modules, block handlers
  and CLI scripts, independent of the layout collection runtime.

Version
-------

- The current version of Exponential Layouts Tags Query is 1.0.0
- Last Major update: July 30, 2026

Copyright
---------

- Exponential Layouts Tags Query is copyright 1998 - 2026 7x
- See: [LICENSE.md](LICENSE.md) for more information on the terms of the
  copyright and license

License
-------

Exponential Layouts Tags Query is licensed under the GNU General Public
License.

The complete license agreement is included in the [LICENSE.md](LICENSE.md)
file.

Exponential Layouts Tags Query is free software: you can redistribute it
and/or modify it under the terms of the GNU General Public License as
published by the Free Software Foundation, either version 2 of the License
or at your option a later version.

Exponential Layouts Tags Query is distributed in the hope that it will be
useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General
Public License for more details.

The GNU GPL gives you the right to use, modify and redistribute
Exponential Layouts Tags Query under certain conditions. The GNU GPL
license is distributed with the software, see the file
[LICENSE.md](LICENSE.md).

It is also available at http://www.gnu.org/licenses/gpl.txt

You should have received a copy of the GNU General Public License along with
Exponential Layouts Tags Query in [LICENSE.md](LICENSE.md). If not, see
http://www.gnu.org/licenses/.

Using Exponential Layouts Tags Query under the terms of the GNU GPL is free
(as in freedom).

For more information or questions please contact info@se7enx.com

Requirements
------------

The following requirements exist for using the Exponential Layouts Tags
Query extension:

Exponential version
- Make sure you use Exponential 6 / Exponential Legacy.

PHP version
- Make sure you have PHP 8.1, 8.2, 8.3 or 8.4.

Extension dependencies
- `explayouts_content_browser` — the query returns
  `expLayoutsContentBrowserItem` objects. Activate it first.
- `explayouts` — required only for the layout collection integration (the
  `exp_content_tags` query type and its handler live there); the class in
  this extension also works standalone.
- Netgen Tags (`eztags` datatype) is optional; `ezkeyword` works out of the
  box.

Installation
------------

Installation is the standard extension procedure: place the extension in
`extension/explayouts_tags_query`, activate it after its dependencies via
`ActiveExtensions[]` (or `ActiveAccessExtensions[]` for a single
siteaccess), regenerate autoloads with
`php bin/php/ezpgenerateautoloads.php -e` and clear caches with
`php bin/php/ezcache.php --clear-all --purge --allow-root-user`.

See [INSTALL.md](INSTALL.md) for the complete step-by-step installation
instructions.

Usage
-----

The extension ships one key class:

| Class | File | Purpose |
|-------|------|---------|
| `expLayoutsTagsQuery` | `classes/explayoutstagsquery.php` | Finds subtree items matching tags from parameters, a content field or the query string |

A quick example:

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

A "more like this" box driven by the viewed article's keywords:

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

Supported tag datatypes: `ezkeyword` (built-in), `eztags` (when the Netgen
Tags extension is installed) and any string/CSV attribute as a fallback.

For layout collections, the Exponential Layouts collection runtime
registers the tags query type in
`extension/explayouts/settings/explayouts.ini.append.php`:

```ini
[QuerySettings]
AvailableQueries[]=exp_content_tags

[QueryType_exp_content_tags]
Name=Exp tags
Handler=expLayoutsTagsQueryHandler
```

Pick `exp_content_tags` as the query type of a dynamic collection in the
layouts admin UI. That handler class ships with the `explayouts` extension;
the class in this extension is the standalone port of the upstream package
for direct PHP use (custom modules, block handlers, CLI scripts).

The full usage guide in [doc/USAGE.md](doc/USAGE.md) covers the complete
parameter reference, tag sources and datatypes, usage scenarios (URL-driven
tag cloud landing pages, "more like this" boxes, strict multi-tag matches
within a section, CLI usage), how the two layers relate, and the three
customization layers of this stack: the settings layer (overriding the
query type `Name=` / `Handler=` through the INI configuration cascade), the
template layer (collection items are rendered by your layouts block
templates) and the PHP layer (the `protected` stage methods and custom
handler classes).

Documentation
-------------

| Document | Description |
|----------|-------------|
| [INSTALL.md](INSTALL.md) | Requirements, dependencies and step-by-step activation instructions |
| [doc/USAGE.md](doc/USAGE.md) | Parameter reference, tag sources, scenarios and the settings/template/PHP customization layers |
| [doc/FAQ.md](doc/FAQ.md) | Frequently asked questions and answers |
| [doc/TODO.md](doc/TODO.md) | Known gaps and planned improvements |
| [doc/SUPPORT.md](doc/SUPPORT.md) | Where and how to get help |
| [LICENSE.md](LICENSE.md) | The complete GNU General Public License agreement |

Troubleshooting
---------------

Read the FAQ
- Some problems are more common than others. The most common ones are listed
  in [doc/FAQ.md](doc/FAQ.md).

Use our support systems
- If you find a bug or defect, please report it to the
  [Exponential Layouts Tags Query: Issue Tracker](https://github.com/se7enxweb/explayouts_tags_query/issues)
- For commercial support and custom development please visit
  [se7enx.com](https://se7enx.com)
