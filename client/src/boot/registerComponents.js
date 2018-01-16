import Injector from 'lib/Injector';
import FocusPointField from 'components/FocusPointField';

const registerComponents = () => {
  Injector.component.register('FocusPointField', FocusPointField);
};

export default registerComponents;
