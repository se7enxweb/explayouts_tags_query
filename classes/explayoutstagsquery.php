<?php
class expLayoutsTagsQuery
{
    public function execute( $params = array() )
    {
        $tags = $this->collectTags( $params );
        if ( empty( $tags ) )
            return array();

        $parentId = isset( $params['parent_id'] ) ? (int)$params['parent_id'] : 2;
        $node = eZContentObjectTreeNode::fetch( $parentId );
        if ( !$node instanceof eZContentObjectTreeNode )
            return array();

        $items = array();

        $subtreeParams = array(
            'Depth' => isset( $params['depth'] ) ? (int)$params['depth'] : 10,
            'Limit' => isset( $params['subtree_limit'] ) ? (int)$params['subtree_limit'] : 1000,
            'Offset' => 0,
            'SortBy' => false,
        );

        $nodes = eZContentObjectTreeNode::subTreeByNodeID( $subtreeParams, $parentId );
        if ( !is_array( $nodes ) )
            return array();

        foreach ( $nodes as $node )
        {
            if ( !$node instanceof eZContentObjectTreeNode )
                continue;

            if ( !empty( $params['only_main_locations'] ) && (int)$node->attribute( 'node_id' ) !== (int)$node->attribute( 'main_node_id' ) )
                continue;

            $object = $node->attribute( 'object' );
            if ( !$object instanceof eZContentObject )
                continue;

            if ( !$this->objectMatchesTags( $object, $tags, $params ) )
                continue;

            $items[] = new expLayoutsContentBrowserItem( $node );
        }

        $items = $this->filterByContentType( $items, $params );
        $items = $this->sort( $items, $params );
        $items = $this->applyLimitOffset( $items, $params );

        return $items;
    }

    protected function collectTags( $params )
    {
        $tags = array();

        if ( isset( $params['tags'] ) )
        {
            $tags = is_array( $params['tags'] ) ? $params['tags'] : explode( ',', (string)$params['tags'] );
        }

        if ( !empty( $params['use_tags_from_current_content'] ) && !empty( $params['field_definition_identifier'] ) )
        {
            $content = $this->resolveContent( $params );
            if ( $content instanceof eZContentObject )
            {
                $tags = array_merge( $tags, $this->tagsFromContent( $content, $params['field_definition_identifier'] ) );
            }
        }

        if ( !empty( $params['use_tags_from_query_string'] ) && !empty( $params['query_string_param_name'] ) )
        {
            $paramName = (string)$params['query_string_param_name'];
            $http = eZHTTPTool::instance();
            if ( $http->hasGetVariable( $paramName ) )
            {
                $value = $http->getVariable( $paramName );
                if ( is_array( $value ) )
                    $tags = array_merge( $tags, $value );
                else
                    $tags = array_merge( $tags, explode( ',', (string)$value ) );
            }
        }

        $tags = array_map( function( $t ) { return strtolower( trim( (string)$t ) ); }, $tags );
        $tags = array_unique( array_filter( $tags, function( $t ) { return $t !== ''; } ) );

        return array_values( $tags );
    }

    protected function resolveContent( $params )
    {
        if ( !empty( $params['use_current_location'] ) && !empty( $params['location_id'] ) )
        {
            $node = eZContentObjectTreeNode::fetch( (int)$params['location_id'] );
            if ( $node instanceof eZContentObjectTreeNode )
                return $node->attribute( 'object' );
        }

        if ( !empty( $params['content_id'] ) )
            return eZContentObject::fetch( (int)$params['content_id'] );

        if ( !empty( $params['location_id'] ) )
        {
            $node = eZContentObjectTreeNode::fetch( (int)$params['location_id'] );
            if ( $node instanceof eZContentObjectTreeNode )
                return $node->attribute( 'object' );
        }

        return null;
    }

    protected function tagsFromContent( $content, $fieldIdentifiers )
    {
        $identifiers = explode( ',', (string)$fieldIdentifiers );
        $identifiers = array_map( 'trim', $identifiers );

        $dataMap = $content->dataMap();
        $tags = array();

        foreach ( $dataMap as $identifier => $attribute )
        {
            if ( !in_array( $identifier, $identifiers, true ) && !in_array( '', $identifiers, true ) )
                continue;

            $tags = array_merge( $tags, $this->attributeTags( $attribute ) );
        }

        return $tags;
    }

    protected function attributeTags( $attribute )
    {
        if ( !$attribute instanceof eZContentObjectAttribute )
            return array();

        $datatype = $attribute->attribute( 'data_type_string' );
        $values = array();

        if ( $datatype === 'ezkeyword' )
        {
            $content = $attribute->content();
            if ( $content instanceof eZKeyword )
                $values = $content->keywordArray();
        }
        elseif ( $datatype === 'eztags' )
        {
            $content = $attribute->content();
            if ( is_array( $content ) )
            {
                foreach ( $content as $tag )
                {
                    if ( is_object( $tag ) && method_exists( $tag, 'attribute' ) )
                        $values[] = (string)$tag->attribute( 'keyword' );
                    elseif ( is_string( $tag ) || is_numeric( $tag ) )
                        $values[] = (string)$tag;
                }
            }
            elseif ( is_object( $content ) && method_exists( $content, 'getKeyword' ) )
            {
                $values[] = (string)$content->getKeyword();
            }
        }
        else
        {
            $string = $attribute->toString();
            $values = explode( ',', $string );
        }

        return array_map( function( $v ) { return strtolower( trim( (string)$v ) ); }, $values );
    }

    protected function objectMatchesTags( $object, $tags, $params )
    {
        $fieldIdentifier = isset( $params['field_definition_identifier'] ) ? (string)$params['field_definition_identifier'] : '';
        $dataMap = $object->dataMap();

        $allValues = array();
        foreach ( $dataMap as $identifier => $attribute )
        {
            if ( $fieldIdentifier !== '' && $identifier !== $fieldIdentifier )
                continue;

            $allValues = array_merge( $allValues, $this->attributeTags( $attribute ) );
        }

        $allValues = array_unique( array_filter( $allValues ) );
        $logic = isset( $params['tags_filter_logic'] ) ? (string)$params['tags_filter_logic'] : 'any';

        if ( $logic === 'all' )
        {
            foreach ( $tags as $tag )
            {
                if ( !in_array( $tag, $allValues, true ) )
                    return false;
            }
            return true;
        }

        foreach ( $tags as $tag )
        {
            if ( in_array( $tag, $allValues, true ) )
                return true;
        }

        return false;
    }

    protected function filterByContentType( $items, $params )
    {
        $types = isset( $params['content_types'] ) ? (array)$params['content_types'] : array();
        if ( empty( $types ) )
            return $items;

        $filter = isset( $params['content_types_filter'] ) ? (string)$params['content_types_filter'] : 'include';
        $filtered = array();

        foreach ( $items as $item )
        {
            $identifier = $item instanceof expLayoutsContentBrowserItem
                ? (string)$item->attribute( 'class_identifier' )
                : '';

            $matches = in_array( $identifier, $types, true );

            if ( $filter === 'exclude' )
            {
                if ( !$matches )
                    $filtered[] = $item;
            }
            else
            {
                if ( $matches )
                    $filtered[] = $item;
            }
        }

        return $filtered;
    }

    protected function sort( $items, $params )
    {
        $sortType = isset( $params['sort_type'] ) ? (string)$params['sort_type'] : 'date_published';
        $direction = isset( $params['sort_direction'] ) ? strtolower( (string)$params['sort_direction'] ) : 'desc';
        $ascending = $direction === 'asc';

        $map = array(
            'date_published' => 'object_published',
            'date_modified' => 'object_modified',
            'content_name' => 'name',
            'location_priority' => 'priority',
        );

        if ( !isset( $map[$sortType] ) )
            return $items;

        $field = $map[$sortType];

        usort(
            $items,
            function( $a, $b ) use ( $field, $ascending )
            {
                $valueA = $a->attribute( $field );
                $valueB = $b->attribute( $field );

                if ( is_numeric( $valueA ) && is_numeric( $valueB ) )
                {
                    return $ascending
                        ? ( $valueA <=> $valueB )
                        : ( $valueB <=> $valueA );
                }

                $cmp = strnatcasecmp( (string)$valueA, (string)$valueB );
                return $ascending ? $cmp : -$cmp;
            }
        );

        return $items;
    }

    protected function applyLimitOffset( $items, $params )
    {
        $limit = isset( $params['limit'] ) ? (int)$params['limit'] : 0;
        $offset = isset( $params['offset'] ) ? (int)$params['offset'] : 0;

        if ( $offset > 0 || $limit > 0 )
            $items = array_slice( $items, $offset, $limit > 0 ? $limit : null );

        return $items;
    }
}
