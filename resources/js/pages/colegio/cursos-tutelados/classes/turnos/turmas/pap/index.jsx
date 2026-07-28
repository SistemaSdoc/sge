import { GrupoPapCards } from './components/grupo-pap-cards';

export default function Index({ gruposPap = [], can }) {
 

  return <GrupoPapCards grupos={gruposPap} can={can} />;
}
