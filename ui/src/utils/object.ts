// ui/src/utils/object.ts
// Utility function to convert a JavaScript object to FormData.
// Useful for sending multipart/form-data requests.

export function objectToFormData(obj: any, form?: FormData, namespace?: string) {
  const fd = form || new FormData();
  let formKey: string;

  for (const property in obj) {
    if (obj.hasOwnProperty(property)) {
      if (namespace) {
        formKey = namespace + '[' + property + ']';
      } else {
        formKey = property;
      }

      // If the property is an object or array, recursively call objectToFormData
      if (typeof obj[property] === 'object' && !(obj[property] instanceof File)) {
        objectToFormData(obj[property], fd, formKey);
      } else {
        // Otherwise, add the property to the FormData object
        fd.append(formKey, obj[property]);
      }
    }
  }

  return fd;
}
