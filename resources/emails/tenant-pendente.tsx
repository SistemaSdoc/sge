import {
  Body,
  Button,
  Container,
  Head,
  Heading,
  Hr,
  Html,
  Preview,
  Section,
  Text,
  Tailwind,
  Link,
  Img,
} from '@react-email/components';

interface TenantPendenteProps {
  nomeUser?: string;
  nomeInstituicao?: string;
  sigla?: string;
  url?: string;
}

export const TenantPendente = ({
  nomeUser,
  nomeInstituicao,
  sigla,
  url,
}: TenantPendenteProps) => {
  return (
    <Html>
      <Head />
      <Preview>
        O registo de {nomeInstituicao} foi recebido e aguarda aprovacao.
      </Preview>
      <Tailwind>
        <Body className="mx-auto my-auto bg-white px-2 font-sans">
          <Container className="mx-auto my-[40] max-w-[30rem] rounded border border-solid border-[#eaeaea] p-[20px]">
            <Section className="mt-[32px]">
              <Img
                src={'../../public/images/logo-sge.png'}
                width="40"
                height="37"
                alt="Logo SGE"
                className="mx-auto my-0"
              />
            </Section>
            <Section className="mt-[32px] text-center">
              <Heading className="mx-0 my-[20px] p-0 text-center text-[24px] font-normal text-black">
                Registo recebido com sucesso
              </Heading>
            </Section>

            <Text className="text-[14px] leading-[24px] text-black">
              Ola, <strong>{nomeUser}</strong>!
            </Text>
            <Text className="text-[14px] leading-[24px] text-black">
              O pedido de registo da instituicao{' '}
              <strong>
                {nomeInstituicao} ({sigla})
              </strong>{' '}
              foi recebido com sucesso e encontra-se{' '}
              <strong>pendente de aprovacao</strong>.
            </Text>
            <Text className="text-[14px] leading-[24px] text-black">
              A nossa equipa ira analisar o pedido em breve. Recebera um email
              assim que a sua conta for activada.
            </Text>

            <Hr className="mx-0 my-[26px] w-full border border-solid border-[#eaeaea]" />

            <Section className="mt-[32px] mb-[32px] text-center">
              <Button
                className="rounded bg-[#000000] px-5 py-3 text-center text-[12px] font-semibold text-white no-underline"
                href={url}
              >
                Ver Estado da Conta
              </Button>
            </Section>

            <Text className="text-[14px] leading-[24px] text-black">
              ou copie e cole este link no seu navegador:{' '}
              <Link href={url} className="text-blue-600 no-underline">
                {url}
              </Link>
            </Text>

            <Hr className="mx-0 my-[26px] w-full border border-solid border-[#eaeaea]" />

            <Text className="text-[12px] leading-[24px] text-[#666666]">
              Se nao reconhece este registo, ignore este email. Se tiver
              duvidas, contacte o nosso suporte.
            </Text>
          </Container>
        </Body>
      </Tailwind>
    </Html>
  );
};

TenantPendente.PreviewProps = {
  nomeUser: 'Paulina Capitao',
  nomeInstituicao: 'Colegio Universitario de Angola',
  sigla: 'CUA',
  url: 'http://cua.sge.localhost',
} as TenantPendenteProps;

export default TenantPendente;
