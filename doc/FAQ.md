# FAQ — explayouts_tags_query

## Do I need the Netgen Tags extension?

No. `ezkeyword` is supported out of the box. `eztags` fields are read by duck typing (objects exposing `attribute( 'keyword' )` or `getKeyword()`), so the query benefits from Netgen Tags when it is installed but has no hard dependency on it. Any other attribute is read via `toString()` and split on commas.

## Is tag matching case-sensitive?

No. All tags — from parameters, content fields and the query string — are trimmed and lowercased before comparison, and candidate values are normalized the same way. Matching is exact per keyword after normalization, not substring.

## What is the difference between `any` and `all` logic?

`any` (the default) returns an item when at least one requested tag appears among the item's tag values. `all` requires every requested tag to be present on the item.

## Why are items missing from the result on a large site?

The query fetches at most `subtree_limit` nodes (default 1000, `depth` 10) under `parent_id` and filters them in memory. Content beyond that window is never examined. Raise `subtree_limit` / lower `parent_id` scope, or split collections per section.

## Is this the class my layout collection uses?

Not directly. Dynamic collections configured with the `exp_content_tags` query type are executed by `expLayoutsTagsQueryHandler`, which ships with the `explayouts` extension. This extension is the standalone port of the upstream package for direct PHP use.

## Why does `execute()` fail when I pass `content_types` or a `sort_type`?

Known gap: `filterByContentType()` and `sort()` call `$item->attribute( ... )` on `expLayoutsContentBrowserItem`, which does not define an `attribute()` method yet. See [TODO.md](TODO.md). Until fixed, omit those parameters and filter/sort the returned items yourself using their public properties.
