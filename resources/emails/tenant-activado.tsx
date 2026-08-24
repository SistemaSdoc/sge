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
  Row,
  Column,
  Img,
} from '@react-email/components';

interface TenantActivadoProps {
  nomeUser?: string;
  nomeInstituicao?: string;
  sigla?: string;
  email?: string;
  url?: string;
}

export const TenantActivado = ({
  nomeUser,
  nomeInstituicao,
  sigla,
  email,
  url,
}: TenantActivadoProps) => {
  return (
    <Html>
      <Head />
      <Preview>
        A conta de {nomeInstituicao} foi activada. Já pode aceder à plataforma.
      </Preview>
      <Tailwind>
        <Body className="mx-auto my-auto bg-white px-2 font-sans">
          <Container className="mx-auto my-[40px] max-w-[465px] rounded border border-solid border-[#eaeaea] p-[20px]">
            {/* Header */}
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
                Conta activada com sucesso!
              </Heading>
            </Section>

            {/* Body */}
            <Text className="text-[14px] leading-[24px] text-black">
              Olá, <strong>{nomeUser}</strong>!
            </Text>
            <Text className="text-[14px] leading-[24px] text-black">
              A conta da instituição{' '}
              <strong>
                {nomeInstituicao} ({sigla})
              </strong>{' '}
              foi aprovada e já pode aceder à plataforma SGE.
            </Text>

            <Hr className="mx-0 my-[26px] w-full border border-solid border-[#eaeaea]" />

            {/* Credenciais */}
            <Text className="text-[14px] leading-[24px] font-semibold text-black">
              As suas credenciais de acesso:
            </Text>
            <Section className="rounded bg-[#f4f4f5] px-[16px] py-[12px]">
              <Row>
                <Column>
                  <Text className="m-0 text-[13px] leading-[24px] text-[#666666]">
                    Email
                  </Text>
                  <Text className="m-0 text-[14px] leading-[24px] font-semibold text-black">
                    {email}
                  </Text>
                </Column>
              </Row>
              <Row className="mt-[8px]">
                <Column>
                  <Text className="m-0 text-[13px] leading-[24px] text-[#666666]">
                    Password temporária
                  </Text>
                  <Text className="m-0 text-[14px] leading-[24px] font-semibold text-black">
                    12345678
                  </Text>
                </Column>
              </Row>
            </Section>
            <Text className="text-[12px] leading-[24px] text-[#666666]">
              Altere a password após o primeiro acesso.
            </Text>

            <Hr className="mx-0 my-[26px] w-full border border-solid border-[#eaeaea]" />

            {/* Button */}
            <Section className="mt-[32px] mb-[32px] text-center">
              <Button
                className="rounded bg-[#000000] px-5 py-3 text-center text-[12px] font-semibold text-white no-underline"
                href={url}
              >
                Aceder à Plataforma
              </Button>
            </Section>

            <Text className="text-[14px] leading-[24px] text-black">
              ou copie e cole este link no seu navegador:{' '}
              <Link href={url} className="text-blue-600 no-underline">
                {url}
              </Link>
            </Text>

            <Hr className="mx-0 my-[26px] w-full border border-solid border-[#eaeaea]" />

            {/* Footer */}
            <Text className="text-[12px] leading-[24px] text-[#666666]">
              Se não reconhece esta conta, contacte o nosso suporte
              imediatamente.
            </Text>
          </Container>
        </Body>
      </Tailwind>
    </Html>
  );
};

TenantActivado.PreviewProps = {
  nomeUser: 'Paulina Capitão',
  nomeInstituicao: 'Colégio Universitário de Angola',
  sigla: 'CUA',
  email: 'capitaopaulinafernando@gmail.com',
  url: 'http://cua.sge.localhost',
} as TenantActivadoProps;

export default TenantActivado;
