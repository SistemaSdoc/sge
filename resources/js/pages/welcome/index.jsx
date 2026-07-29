import { useEffect } from 'react';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import NavigationMenu from './components/layout/navigation-menu';
import Footer from './components/layout/footer';
import Hero from './components/sections/hero';
import Marquee from './components/sections/marquee';
import Modulos from './components/sections/modulos';
import Plataforma from './components/sections/plataforma';
import Clientes from './components/sections/clientes';
import Cta from './components/sections/cta';

gsap.registerPlugin(ScrollTrigger);

export default function Welcome() {
  useEffect(() => {
    const ctx = gsap.context(() => {
      // Sequência de entrada do hero
      const heroTl = gsap.timeline({ defaults: { ease: 'power3.out' } });
      heroTl
        .to('#hero-title', { opacity: 1, y: 0, duration: 1 })
        .to('#hero-sub', { opacity: 1, y: 0, duration: 0.8 }, '-=0.6')
        .to('#hero-cta', { opacity: 1, y: 0, duration: 0.8 }, '-=0.5')
        .to('#hero-meta', { opacity: 1, y: 0, duration: 0.8 }, '-=0.5')
        .fromTo(
          '#tc-1',
          { opacity: 0, x: 20 },
          { opacity: 1, x: 0, duration: 0.7 },
          '-=1',
        )
        .fromTo(
          '#tc-2',
          { opacity: 0, x: 20 },
          { opacity: 1, x: 0, duration: 0.7 },
          '-=0.55',
        )
        .fromTo(
          '#tc-3',
          { opacity: 0, x: 20 },
          { opacity: 1, x: 0, duration: 0.7 },
          '-=0.55',
        );

      // Flutuação idle da pilha de tenants
      gsap.to('#tc-1', {
        y: '+=6',
        rotate: -1.2,
        duration: 3.2,
        repeat: -1,
        yoyo: true,
        ease: 'sine.inOut',
      });
      gsap.to('#tc-2', {
        y: '+=5',
        rotate: 0.8,
        duration: 2.8,
        repeat: -1,
        yoyo: true,
        ease: 'sine.inOut',
        delay: 0.3,
      });

      // Scroll infinito do marquee
      gsap.to('#marquee', {
        xPercent: -50,
        duration: 22,
        repeat: -1,
        ease: 'none',
      });

      // Reveals no scroll (exceto os do hero, já tratados acima)
      document
        .querySelectorAll(
          '.reveal:not(#hero-title):not(#hero-sub):not(#hero-cta):not(#hero-meta)',
        )
        .forEach((el) => {
          gsap.fromTo(
            el,
            { opacity: 0, y: 24 },
            {
              opacity: 1,
              y: 0,
              duration: 0.9,
              ease: 'power3.out',
              scrollTrigger: { trigger: el, start: 'top 85%' },
            },
          );
        });

      // Stagger no grid de módulos
      gsap.utils.toArray('#modulos .reveal').forEach((el, i) => {
        if (!el.matches('h2')) {
          gsap.fromTo(
            el,
            { opacity: 0, y: 20 },
            {
              opacity: 1,
              y: 0,
              duration: 0.7,
              delay: (i % 3) * 0.08,
              ease: 'power3.out',
              scrollTrigger: { trigger: el, start: 'top 90%' },
            },
          );
        }
      });
    });

    return () => ctx.revert();
  }, []);

  return (
    <div className="overflow-x-hidden bg-bg font-sans text-text antialiased selection:bg-accent selection:text-black">
      <NavigationMenu />
      <div className="wrap">
        <Hero />
        <Marquee />
        <Modulos />
        <Plataforma />
        <Clientes />
        <Cta />
        <Footer />
      </div>
    </div>
  );
}
