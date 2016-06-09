# $Id: make-curl-ca-bundle-crt.sh,v 1.1 2014/05/01 16:45:04 tk Exp $
# $Source: /encfs/checkout/antonio/cvs/SecCommerceDev/seccommerce/secsignerid/php/make-curl-ca-bundle-crt.sh,v $
# $Log: make-curl-ca-bundle-crt.sh,v $
# Revision 1.1  2014/05/01 16:45:04  tk
# New.
#

# Creates the curl-ca-bundle.crt for PHP from the root certifificates of some
# important trustcenters.

#find ../../certs/countries/USA/comodo/ ../../certs/countries/USA/digicert/ ../../certs/countries/USA/entrust/ ../../certs/countries/USA/geotrust/ ../../certs/countries/USA/godaddy/ ../../certs/countries/USA/symantec/ ../../certs/countries/ZA/thawte/ ../../certs/countries/IL/startcom/ -name '*.der' -exec openssl x509 -in {} -inform DER \; >curl-ca-bundle.crt
find ../SecCommerceDev/seccommerce/certs/countries/USA/comodo/ ../SecCommerceDev/seccommerce/certs/countries/USA/digicert/ ../SecCommerceDev/seccommerce/certs/countries/USA/entrust/ ../SecCommerceDev/seccommerce/certs/countries/USA/geotrust/ ../SecCommerceDev/seccommerce/certs/countries/USA/godaddy/ ../SecCommerceDev/seccommerce/certs/countries/USA/symantec/ ../SecCommerceDev/seccommerce/certs/countries/ZA/thawte/ ../SecCommerceDev/seccommerce/certs/countries/IL/startcom/ -name '*.der' -exec openssl x509 -in {} -inform DER \; >curl-ca-bundle.crt
