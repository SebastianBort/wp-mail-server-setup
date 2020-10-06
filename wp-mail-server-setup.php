<?php  
/*
Plugin Name: Konfiguracja serwera pocztowego
Description: Prosta konfiguracja serwera pocztowego SMTP do wysyłania listów. Dane do serwera do wpisania w zakładce Ustawienia -> Ogólne.
Version: 1.0.0
Author: Sebastian Bort
*/  
 
class WP_Mail_Server_Setup {

    private $fields = [
              'smtp_host' => 'Host',
              'smtp_port' => 'Port',
              'smtp_username' => 'Nazwa użytkownika',
              'smtp_password' => 'Hasło do konta',
              'smtp_secure' => 'Rodzaj szyfrowania',
              'smtp_fromname' => 'Nazwa nadawcy',
              'smtp_fromemail' => 'Adres e-mail nadawcy',
              'smtp_html' => 'Wysyłaj jako HTML',
    ];
    
    private $required;
    
    public function __construct() {
        
        add_action('phpmailer_init', [$this, 'set_smtp'] );
        add_action('admin_init' , [$this, 'register_fields']); 
        
        add_filter('wp_mail_content_type' , [$this, 'set_content_type']);                   
        add_filter('wp_mail_from', [$this, 'set_from']);
        add_filter('wp_mail_from_name', [$this, 'set_from_name']);

        add_filter('plugin_action_links_' . plugin_basename(__FILE__),[$this, 'plugins_list_actions']);        
    }    
    
    public function set_content_type($content_type) {
        
           $send_as_html = get_option('smtp_html');
           return $send_as_html ? 'text/html' : $content_type;
    }   
    
    public function set_from($default) {       
        
        $mail_from = get_option('smtp_fromemail');
        return empty($mail_from) ? $default : $mail_from;
    }
    
    public function set_from_name($default) {       
        
        $mail_from_name = get_option('smtp_fromname');
        return empty($mail_from_name) ? $default : $mail_from_name;
    }    

    public function set_smtp($phpmailer) {
        
          $options = [];
          foreach($this->fields AS $field => $desc) {
              $options[$field] = get_option($field);   
          }
                    
          if(!empty($options['smtp_host']) && !empty($options['smtp_username']) && !empty($options['smtp_password'])) {
          
              $phpmailer->isSMTP();     
              $phpmailer->Host = $options['smtp_host'];
              $phpmailer->SMTPAuth = true; 
              $phpmailer->Port = empty($options['smtp_port']) ? 25 : $options['smtp_port'];
              $phpmailer->Username = $options['smtp_username'];
              $phpmailer->Password = $options['smtp_password']; 
              
              if(!empty($options['smtp_secure'])) {
                    $phpmailer->SMTPSecure = $options['smtp_secure'];
              }
              
              if(!empty($options['smtp_fromname']) && !empty($options['smtp_fromemail'])) {
                    $phpmailer->From = $options['smtp_fromemail'];
                    $phpmailer->FromName = $options['smtp_fromname'];
              }               
          }
    }
    
    public function smtp_info() {
        echo '<p id="smtp_info">Uzupełnij poniżej dane serwera pocztowego aby aktywować wysyłkę listów przez protokół SMTP.</p>';  
    }
    
    public function register_fields() {

          add_settings_section(  
              'smtp_server',
              'Ustawienia serwera SMTP',
              [$this, 'smtp_info'], 
              'general'
          );

          foreach($this->fields AS $field => $desc) {                
             add_settings_field(
                    $field,
                    $desc,
                    [$this, 'fields_html'],
                    'general', 
                    'smtp_server',
                    [$field]  
                ); 
                register_setting('general', $field);
          }          
    }

  	public function fields_html($args) {
        
        $value = get_option($args[0], '');
        if(!$value) {
              $value = '';
        }  
        
        switch($args[0]) { 
              
              case 'smtp_html': 

                      printf('<label><input type="checkbox" name="%s" value="1" %s></label><br>',
                            $args[0],
                            checked($value, '1', false)
                      );
              break;
              
              case 'smtp_secure':                    
                 
                  $values = [
                      '' => 'Brak',
                      'tls' => 'TLS',
                      'ssl' => 'SSL',
                  ];  
                  foreach($values AS $val => $name) {
                      printf('<label><input type="radio" name="%s" value="%s" %s> %s</label><br>',
                            $args[0],
                            $val, 
                            checked($value, $val, false),
                            $name
                      );
                  }              
              
              break;
              
              default:
            	    
                    printf('<input class="regular-text" type="text" id="%s" name="%s" value="%s">',
                        $args[0],
                        $args[0],
                        esc_attr($value)
                    );              
              break;           
        }    	
  	}       
        
    public function plugins_list_actions($links) {
          $link = '<a href="' . wp_nonce_url(admin_url('options-general.php#smtp_info')) . '">Ustawienia serwera pocztowego</a>';       
          array_unshift($links, $link);                                                                 
      	  return $links;
    }   
}
 
new WP_Mail_Server_Setup();  
 
?>