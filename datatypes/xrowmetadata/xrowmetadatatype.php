﻿<?php

require_once ( 'kernel/common/i18n.php' );

class xrowMetaDataType extends eZDataType
{
    const DATA_TYPE_STRING = 'xrowmetadata';

    /*!
     Initializes with a keyword id and a description.
    */
    function xrowMetaDataType()
    {
        $this->eZDataType( self::DATA_TYPE_STRING, ezi18n( 'kernel/classes/datatypes', 'Metadata', 'Datatype name' ), array( 
            'serialize_supported' => true 
        ) );
    }

    /*!
     Sets the default value.
    */
    function initializeObjectAttribute( $contentObjectAttribute, $currentVersion, $originalContentObjectAttribute )
    {
        if ( $currentVersion != false )
        {
            $originalContentObjectAttributeID = $originalContentObjectAttribute->attribute( 'id' );
            $contentObjectAttributeID = $contentObjectAttribute->attribute( 'id' );
            
            // if translating or copying an object
            if ( $originalContentObjectAttributeID != $contentObjectAttributeID )
            {
                $metadata = $originalContentObjectAttribute->content();
                if ( $metadata instanceof xrowMetadata )
                {
                    //@TODO do something to store the stuff
                }
            }
        }
    }

    /*!
     Validates the input and returns true if the input was
     valid for this datatype.
    */
    function validateObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        if ( $http->hasPostVariable( $base . '_xrowmetadata_data_array_' . $contentObjectAttribute->attribute( 'id' ) ) )
        {
            $data = $http->postVariable( $base . '_xrowmetadata_data_array_' . $contentObjectAttribute->attribute( 'id' ) );
            $classAttribute = $contentObjectAttribute->contentClassAttribute();
            if ( ! $classAttribute->attribute( 'is_information_collector' ) and $contentObjectAttribute->validateIsRequired() )
            {
                if ( $data == "" )
                {
                    $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes', 'Input required.' ) );
                    return eZInputValidator::STATE_INVALID;
                }
                if ( empty( $data['title'] ) )
                {
                    $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes', 'Title required.' ) );
                    return eZInputValidator::STATE_INVALID;
                }
            }
        
        }
        return eZInputValidator::STATE_ACCEPTED;
    }

    /*!
     Fetches the http post var keyword input and stores it in the data instance.
    */
    function fetchObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        if ( $http->hasPostVariable( $base . '_xrowmetadata_data_array_' . $contentObjectAttribute->attribute( 'id' ) ) )
        {
            $data = $http->postVariable( $base . '_xrowmetadata_data_array_' . $contentObjectAttribute->attribute( 'id' ) );
            $meta = self::fillMetaData( $data );
            $contentObjectAttribute->setContent( $meta );
            return true;
        }
        return false;
    }

    /*!
     Does nothing since it uses the data_text field in the content object attribute.
     See fetchObjectAttributeHTTPInput for the actual storing.
    */
    function storeObjectAttribute( $attribute )
    {
        $meta= $attribute->content();
        $xml = new DOMDocument( "1.0", "UTF-8" );
        $xmldom = $xml->createElement( "MetaData" );
        $node = $xml->createElement( "title", $meta->title );
        $xmldom->appendChild( $node );
        $node = $xml->createElement( "keywords", $meta->keywords );
        $xmldom->appendChild( $node );
        $node = $xml->createElement( "description", $meta->description );
        $xmldom->appendChild( $node );
        $node = $xml->createElement( "priority", $meta->priority );
        $xmldom->appendChild( $node );
        $node = $xml->createElement( "change", $meta->change );
        $xmldom->appendChild( $node );
        $node = $xml->createElement( "googlemap", $meta->googlemap );
        $xmldom->appendChild( $node );
        $xml->appendChild( $xmldom );
        $attribute->setAttribute( 'data_text', $xml->saveXML() );
    }

    /*!
     \reimp
    */
    function validateClassAttributeHTTPInput( $http, $base, $attribute )
    {
        return eZInputValidator::STATE_ACCEPTED;
    }

    /*!
     \reimp
    */
    function fixupClassAttributeHTTPInput( $http, $base, $attribute )
    {
    }

    /*!
     \reimp
    */
    function fetchClassAttributeHTTPInput( $http, $base, $attribute )
    {
        return true;
    }
    /*
     * @return xrowMetaData
     */
    function fetchMetaData( $attribute )
    {
    	try
    	{
    	   $xml = new SimpleXMLElement( $attribute->attribute( 'data_text' ) );
    	   $meta = new xrowMetaData( $xml->title, $xml->keywords, $xml->description, $xml->priority, $xml->change, $xml->googlemap );
           return $meta;
    	}
    	catch ( Exception $e )
    	{
    		return new xrowMetaData();
    	}

    	
    }
    /*
     * @return xrowMetaData
     */
    function fillMetaData( $array )
    {
        return new xrowMetaData( $array['title'], $array['keywords'], $array['description'], $array['priority'], $array['change'], $array['googlemap'] );
    }
    /*!
     Returns the content.
    */
    function objectAttributeContent( $attribute )
    {
        return self::fetchMetaData( $attribute );
    }

    /*!
     Returns the meta data used for storing search indeces.
    */
    function metaData( $attribute )
    {
        $meta = self::fetchMetaData( $attribute );
        return $meta->title .' '. $meta->keywords.' '. $meta->description;
    }

    /*!
     \reuturn the collect information action if enabled
    */
    function contentActionList( $classAttribute )
    {
        return array();
    }

    /*!
     Returns the content of the keyword for use as a title
    */
    function title( $attribute, $name = null )
    {
        $meta = self::fetchMetaData( $attribute );
        return $meta->title;
    }

    function hasObjectAttributeContent( $contentObjectAttribute )
    {
    	$meta = self::fetchMetaData( $contentObjectAttribute );
    	if ( $meta instanceof xrowMetaData ) {
    		return true;
    	}
    	else
    	{
    		return false;
    	}
    }

    /*!
     \reimp
    */
    function isIndexable()
    {
        return true;
    }

    /*!
     \return string representation of an contentobjectattribute data for simplified export

    */
    function toString( $contentObjectAttribute )
    {
        return $contentObjectAttribute->attribute( 'data_text' );
    }

    function fromString( $contentObjectAttribute, $string )
    {
        if ( $string != '' )
        {
        	$contentObjectAttribute->setAttribute( 'data_text', $string );
        	$meta = self::fetchMetaData( $contentObjectAttribute );
            $contentObjectAttribute->setContent( $meta );
        }
        return true;
    }

    /*!
     \reimp
     \param package
     \param content attribute

     \return a DOM representation of the content object attribute
    */
    function serializeContentObjectAttribute( $package, $objectAttribute )
    {
    	/*
    	 * @TODO do it later
        $node = $this->createContentObjectAttributeDOMNode( $objectAttribute );
        
        $keyword = new xrowMetaData( );
        $keyword->fetch( $objectAttribute );
        $keyWordString = $keyword->keywordString();
        $dom = $node->ownerDocument;
        $keywordStringNode = $dom->createElement( 'keyword-string', $keyWordString );
        $node->appendChild( $keywordStringNode );
        
        return $node;
        */
    }

    /*!
     \reimp
     Unserailize contentobject attribute

     \param package
     \param contentobject attribute object
     \param domnode object
    */
    function unserializeContentObjectAttribute( $package, $objectAttribute, $attributeNode )
    {
    	       /*
         * @TODO do it later
        $keyWordString = $attributeNode->getElementsByTagName( 'keyword-string' )->item( 0 )->textContent;
        $keyword = new xrowMetaData( );
        $keyword->initializeKeyword( $keyWordString );
        $objectAttribute->setContent( $keyword );
        */
    }
}

eZDataType::register( xrowMetaDataType::DATA_TYPE_STRING, 'xrowMetaDataType' );

?>