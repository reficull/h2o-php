<?php
require_once 'spec_helper.php';

class Describe_local_variable extends SimpleSpec {
    
    function should_retrive_variable_using_array_interface() {

        $c = create_context(array(
            'name' => 'peter',
            'hobbies' => array('football', 'basket ball', 'swimming'),
            'location' => array(
                'address' => '1st Jones St',
                'city' => 'Marry hill', 'state' => 'NSW', 'postcode' => 2320
            ) 
        ));
        $this[$c['name']]->should_be("peter");
        $this[$c['name']]->should_not_be("peter wong");
    }

    function should_set_variable_using_array_interface() {
        $c = create_context();
        $c['name'] = 'peter';
        $c['age'] = 18;
        
        $this[ $c['name'] ]->should_be("peter");
        $this[ $c['age'] ]->should_be(18);
    }
    
    function should_ensure_scope_remain_local_in_a_stack_layer() {
        $c= new H2o_Context(array('name'=> 'peter'));

            $c->push(array('name'=>'wong'));
            
                $c->push(array('name'=>'lee'));
                    $this[$c['name']]->should_be('lee');
                    $this[$c->resolve(':name')]->should_be('lee');
                $c->pop();
                $this[$c->resolve(':name')]->should_be('wong');

            $c->pop();
        $this[$c['name']]->should_be('peter');
    }
}

class Describe_context_lookup_basic_data_types extends SimpleSpec {

    function should_resolve_a_integer() {
        $c= create_context();
        
        $this[$c->resolve('0000')]->should_be(0);
        $this[$c->resolve('-00001')]->should_be(-1);
        $this[$c->resolve('20000')]->should_be(20000);
    }
    
    function should_resolve_a_float_number() {
        $c= create_context();
        
        # Float
        $this[$c->resolve('0.001')]->should_be(0.001);
        $this[$c->resolve('99.999')]->should_be(99.999);
    }
    
    function should_resolve_a_negative_number() {
        $c= create_context();
        
        $this[$c->resolve('-00001')]->should_be(-1);   
    }
    
    function should_resolve_a_string() {
        $c= new H2o_Context;
        $this[$c->resolve('"something"')]->should_be('something');
        $this[$c->resolve("'he hasn\'t eat it yet'")]->should_be("he hasn't eat it yet");
    }
}

class Describe_array_lookup extends SimpleSpec {

    function should_be_access_by_array_index() {
        $c= create_context(array(
            'numbers'   => array(1,2,3,4,1,2,3,4,5),
        ));
        
        $this[$c->resolve(':numbers.0')]->should_be(1);
        $this[$c->resolve(':numbers.1')]->should_be(2);
        $this[$c->resolve(':numbers.8')]->should_be(5);
    }
    
    function should_be_access_by_array_key() {
        $c= create_context(array('person' => array(
            'name' => 'peter','age' => 26, 'tasks'=> array('shopping','sleep')
        )));
        
        $this[$c->resolve(':person.name')]->should_be('peter');
        $this[$c->resolve(':person.age')]->should_be(26);
        $this[$c->resolve(':person.tasks.first')]->should_be('shopping');
    }
    
    function should_resolve_array_like_objects() {
        $c= create_context(array(
            'list' => new ArrayObject(array(
                'item 1', 'item 2', 'item 3'
            )),
            'dict' => new ArrayObject(array(
                'name' => 'peter','seo-url' => 'http://google.com'
            ))
        ));
        $this[$c->resolve(':list.0')]->should_be('item 1');
        $this[$c->resolve(':list.length')]->should_be(3);
        $this[$c->resolve(':dict.name')]->should_be('peter');
        $this[$c->resolve(':dict.seo-url')]->should_be('http://google.com');
    }
    
    function should_resolve_additional_array_property() {
       $c = create_context(array(
           'hobbies'=> array('football', 'basket ball', 'swimming')
       ));
       
       $this[$c->resolve(':hobbies.first')]->should_be('football');
       $this[$c->resolve(':hobbies.last')]->should_be('swimming');
       $this[$c->resolve(':hobbies.length')]->should_be(3);
       $this[$c->resolve(':hobbies.size')]->should_be(3);
    }
}

class Describe_context_lookup extends SimpleSpec {
    
    function should_use_dot_to_access_object_property() {
        $c = create_context(array(
            'location' => (object) array(
                'address' => '1st Jones St',
                'city' => 'Marry hill', 'state' => 'NSW', 
                'postcode' => 2320
            ),
        ));
        $this[$c->resolve(':location.address')]->should_be('1st Jones St');
        $this[$c->resolve(':location.city')]->should_be('Marry hill');
    }
    
    function should_return_null_for_undefined_or_private_object_property() {
        $c = create_context(array(
            'document' => new Document(
                'my business report', 
                'Since Augest 2005, financial projection has..')
        ));
        $this[$c->resolve(':document.uuid')]->should_be_null();   // Private
        $this[$c->resolve(':document.undefined_property')]->should_be_null();
    }

    function should_use_dot_to_perform_method_call() {
        $c = create_context(array(
            'document' => new Document(
                'my business report', 
                'Since Augest 2005, financial projection has..')
        ));
        $this[$c->resolve(':document.to_pdf')]->should_match('/PDF Version :/');
        $this[$c->resolve(':document.to_xml')]->should_match('/<title>my business report<\/title>/');
    }
    
    function should_return_null_for_undefined_or_private_method_call() {
        $c = create_context(array(
            'document' => new Document(
                'my business report', 
                'Since Augest 2005, financial projection has..')
        ));
        
        $this[$c->resolve(':document._secret')]->should_be_null();   // Private
        $this[$c->resolve(':document.undefined_method')]->should_be_null();
    }
}

class Document {
    var $h2o_safe = array('to_pdf', 'to_xml');
    private $uuid;

    function __construct($title, $content) {
        $this->title = $title;
        $this->content = $content;
        $this->uuid = md5($title.time());
    }

    function to_pdf() {
        return "PDF Version : {$this->title}";
    }

    function to_xml() {
        return "<title>{$this->title}</title><content>{$this->content}</content>";
    }

    function _secret() {
        return "secret no longer";
    }
}

function create_context($c = array()) {
    return new H2o_Context($c);
}

?>