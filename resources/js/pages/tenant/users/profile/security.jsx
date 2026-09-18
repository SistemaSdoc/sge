import { Head } from '@inertiajs/react';
import { SecurityData } from './components/security-data';

export default function Security(props) {
  return (
    <>
      <Head title="Segurança" />
      
      <SecurityData {...props} />
    </>
  );
}