#!/bin/sh
grep -hio 'contact.\w\+' src/Treto/PortalBundle/Resources/public/partials/contact*Create.html\
| awk -F '.' '{print $2}' | sort -u |uniq -u | while read m; do
first=$(echo $m | awk '{print substr($1,0,1)}')
rest=$( echo $m | awk '{print substr($1,2)}')
Sc=$(echo $first | tr '[[:lower:]]' '[[:upper:]]' )
[ -z "`grep -i "$m" /var/www/symfony2/src/Treto/PortalBundle/Document/Contacts.php`" ] &&
cat <<EOF
  /** @MongoDB\String */
  protected \$${m};

EOF
done;

echo '    /** --------------------- B/Getters + Setters ---------------------*/'

grep -hio 'contact.\w\+' src/Treto/PortalBundle/Resources/public/partials/contact*Create.html\
| awk -F '.' '{print $2}' | sort -u |uniq -u | while read m; do
first="$(echo $m | awk '{print substr($1,0,1)}')"
rest="$( echo $m | awk '{print substr($1,2)}')"
Sc=$(echo $first | tr '[[:lower:]]' '[[:upper:]]' )
[ -z "`grep -i "$m" /var/www/symfony2/src/Treto/PortalBundle/Document/Contacts.php`" ] &&
cat <<EOF

  /** $m */
  public function set$Sc$rest (\$$m) { 
    \$this->$m = \$$m; return \$this; 
  }
  /** $m */
  public function get$Sc$rest () { 
    return \$this->$m; 
  }
EOF
done;
