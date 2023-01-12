const routes = require('../../../../public/bundles/jsrouting-bundle/fos_js_routes.json');
import UrlGenerator from '../../../../vendor/friendsofsymfony/jsrouting-bundle/Resources/public/js/router.min.js'
UrlGenerator.setRoutingData(routes);

export {UrlGenerator};