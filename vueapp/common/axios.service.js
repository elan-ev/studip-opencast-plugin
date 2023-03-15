import axios from "axios";

let oc_axios = axios.create();

oc_axios.CancelToken = axios.CancelToken;
oc_axios.isCancel    = axios.isCancel;

export default oc_axios;