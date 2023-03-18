<?php

/**
 ** A base module for [leadstate] and [leadstate*]
 **/

/* form_tag handler */

add_action('wpcf7_init', 'nbcpf_add_form_tag_leadstate');

function nbcpf_add_form_tag_leadstate()
{
  wpcf7_add_form_tag(
    array('leadstate', 'leadstate*'),
    'nbcpf_leadstate_form_tag_handler',
    array('name-attr' => true)
  );
}

function nbcpf_leadstate_form_tag_handler($tag)
{
  $wpcf7 = WPCF7_ContactForm::get_current();
  $form_id = $wpcf7->id();
  if (empty($tag->name)) {
    return '';
  }

  $validation_error = wpcf7_get_validation_error($tag->name);

  $class = wpcf7_form_controls_class($tag->type, 'wpcf7-text');

  if (in_array($tag->basetype, array('email', 'url', 'tel'))) {
    $class .= ' wpcf7-validates-as-' . $tag->basetype;
  }

  if ($validation_error) {
    $class .= ' wpcf7-not-valid';
  }

  $atts = array();

  $atts['size'] = $tag->get_size_option();
  $atts['maxlength'] = $tag->get_maxlength_option();
  $atts['minlength'] = $tag->get_minlength_option();

  if (
    $atts['maxlength'] && $atts['minlength']
    && $atts['maxlength'] < $atts['minlength']
  ) {
    unset($atts['maxlength'], $atts['minlength']);
  }

  $atts['class'] = $tag->get_class_option($class);

  if ($tag->get_id_option()) {
    $atts['id'] = $tag->get_id_option();
  } else {
    $atts['id'] = $tag->name;
  }

  $id = str_replace('-', '_', $atts['id']);
  $atts['tabindex'] = $tag->get_option('tabindex', 'signed_int', true);

  $atts['autocomplete'] = $tag->get_option(
    'autocomplete',
    '[-0-9a-zA-Z]+',
    true
  );

  if ($tag->has_option('readonly')) {
    $atts['readonly'] = 'readonly';
  }

  if ($tag->is_required()) {
    $atts['aria-required'] = 'true';
  }

  $atts['aria-invalid'] = $validation_error ? 'true' : 'false';

  $value = (string) reset($tag->values);

  if ($tag->has_option('placeholder') || $tag->has_option('watermark')) {
    $atts['placeholder'] = $value;
    $value = '';
  }

  $value = $tag->get_default_option($value);

  $value = wpcf7_get_hangover($tag->name, $value);

  $atts['value'] = $value;

  $atts['type'] = 'text';

  $atts['name'] = $tag->name;
  $atts_id = $atts['id'];
  $atts = wpcf7_format_atts($atts);

  $html = sprintf(
    '<span class="wpcf7-form-control-wrap select-state-text" data-name="%1$s"><input %2$s />%3$s</span> <script>
                        var lead_state_' . $id . ' = []
                        document.getElementById("lead_country_cf_' . $form_id . '").addEventListener("change", (event) => {
                            lead_state_' . $id . ' = []
                            var country_id = event.target.value;
                            
                            if(states.length>0){
                                for (var i = 0; i < states.length; i++){
                                    if (states[i].country_code == country_id){
                                        lead_state_' . $id . '.push(states[i].name);
                                    } else {
                                       
                                    }
                                }
                            }
                            autocomplete(document.getElementById("' . $atts_id . '"), lead_state_' . $id . ');
                        });

                       
                        </script>',
    sanitize_html_class($tag->name),
    $atts,
    $validation_error
  );

  return $html;
}


/* Validation filter */


add_filter('wpcf7_validate_leadstate', 'nbcpf_leadstate_validation_filter', 10, 2);
add_filter('wpcf7_validate_leadstate*', 'nbcpf_leadstate_validation_filter', 10, 2);

function nbcpf_leadstate_validation_filter($result, $tag)
{
  $type = $tag->type;
  $name = $tag->name;

  $value = isset($_POST[$name]) ? (string) sanitize_text_field($_POST[$name]) : '';

  if ($tag->is_required() && '' == $value) {
    $result->invalidate($tag, wpcf7_get_message('invalid_required'));
  }


  return $result;
}


/* Tag generator */

add_action('wpcf7_admin_init', 'nbcpf_add_tag_generator_leadstate', 20);

function nbcpf_add_tag_generator_leadstate()
{
  $tag_generator = WPCF7_TagGenerator::get_instance();
  $tag_generator->add(
    'leadstate',
    __('lead state', 'leadtrail'),
    'nbcpf_tag_generator_leadstate'
  );
}

function nbcpf_tag_generator_leadstate($contact_form, $args = '')
{
  $args = wp_parse_args($args, array());
  $type = 'leadstate';

  $description = __("Generate a form-tag for a quanity text input field.", 'leadtrail');

  //$desc_link = wpcf7_link( __( 'https://contactform7.com/text-fields/', 'leadtrail' ), __( 'Text Fields', 'leadtrail' ) );
  $desc_link = '';
?>
  <div class="control-box">
    <fieldset>
      <legend><?php echo sprintf(esc_html($description), $desc_link); ?></legend>

      <table class="form-table">
        <tbody>
          <tr>
            <th scope="row"><?php echo esc_html(__('Field type', 'leadtrail')); ?></th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><?php echo esc_html(__('Field type', 'leadtrail')); ?></legend>
                <label><input type="checkbox" name="required" /> <?php echo esc_html(__('Required field', 'leadtrail')); ?></label>
              </fieldset>
            </td>
          </tr>

          <tr>
            <th scope="row"><label for="<?php echo esc_attr($args['content'] . '-name'); ?>"><?php echo esc_html(__('Name', 'leadtrail')); ?></label></th>
            <td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr($args['content'] . '-name'); ?>" /></td>
          </tr>

          <tr>
            <th scope="row"><label for="<?php echo esc_attr($args['content'] . '-values'); ?>"><?php echo esc_html(__('Default value', 'leadtrail')); ?></label></th>
            <td><input type="text" name="values" class="oneline" id="<?php echo esc_attr($args['content'] . '-values'); ?>" /><br />
              <label><input type="checkbox" name="placeholder" class="option" /> <?php echo esc_html(__('Use this text as the placeholder of the field', 'leadtrail')); ?></label>
            </td>
          </tr>

          <tr>
            <th scope="row"><label for="<?php echo esc_attr($args['content'] . '-id'); ?>"><?php echo esc_html(__('Id attribute', 'leadtrail')); ?></label></th>
            <td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr($args['content'] . '-id'); ?>" /></td>
          </tr>

          <tr>
            <th scope="row"><label for="<?php echo esc_attr($args['content'] . '-class'); ?>"><?php echo esc_html(__('Class attribute', 'leadtrail')); ?></label></th>
            <td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr($args['content'] . '-class'); ?>" /></td>
          </tr>

        </tbody>
      </table>
    </fieldset>
  </div>

  <div class="insert-box">
    <input type="text" name="<?php echo $type; ?>" class="tag code" readonly="readonly" onfocus="this.select()" />

    <div class="submitbox">
      <input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr(__('Insert Tag', 'leadtrail')); ?>" />
    </div>

    <br class="clear" />

    <p class="description mail-tag"><label for="<?php echo esc_attr($args['content'] . '-mailtag'); ?>"><?php echo sprintf(esc_html(__("To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.", 'leadtrail')), '<strong><span class="mail-tag"></span></strong>'); ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr($args['content'] . '-mailtag'); ?>" /></label></p>
  </div>
<?php
}
