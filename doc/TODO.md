# TODO — explayouts_tags_query

- `filterByContentType()` and `sort()` call `$item->attribute( 'class_identifier' )` / `$item->attribute( $field )` on `expLayoutsContentBrowserItem`, which has no `attribute()` method — content-type filtering and sorting fail when reached. Either add an eZ-style `attribute()` accessor to the item class or switch to its public properties.
- The sort map targets `object_published`, `object_modified` and `priority`, none of which exist on the item (`published`/`modified` are formatted strings; node priority is not captured). Sorting needs real timestamp/priority data on the item.
- Matching is an in-memory scan of up to `subtree_limit` (default 1000) nodes with a full `dataMap()` per candidate object — no indexed tag lookup. Large subtrees are both slow and silently truncated.
- In `execute()`, the initial `eZContentObjectTreeNode::fetch( $parentId )` result is only used as an existence check and is then shadowed by the `foreach` loop variable `$node`; clean up the variable usage.
- `use_current_location` requires an explicit `location_id` parameter; there is no automatic detection of the currently viewed page (unlike `expLayoutsTagsQueryHandler` in `explayouts`).
- `settings/design.ini.append.php` registers `explayouts_tags_query` as a design extension, but the extension has no `design/` directory.
