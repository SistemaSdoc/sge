import{n as e,t}from"./jsx-runtime-DIBGU2nq.js";import{N as n,S as ee,Xt as r}from"./app-CURijQHJ.js";import{a as te,i,n as ne,o as a,r as re,t as ie}from"./empty-Crr1sh84.js";var ae=e(),o=t();function s(e){let t=(0,ae.c)(47),{icon:s,title:c,description:l,action:u,secondaryActions:d,variant:f,className:oe}=e,p=c===void 0?`Nenhum dado disponível`:c,m=l===void 0?`Parece que ainda não há nada por aqui`:l,h;t[0]===d?h=t[1]:(h=d===void 0?[]:d,t[0]=d,t[1]=h);let g=h,_=f===void 0?`default`:f,v=ee(),y=u&&(u.href||u.onClick),b=v&&_!==`minimal`?`compact`:_,x=v?`py-12 gap-5`:`py-16 gap-6`,S=v?`py-6 gap-3`:`py-8 gap-4`,C=v?`py-8 gap-3`:`py-12 gap-4`,w;t[2]!==x||t[3]!==S||t[4]!==C?(w={default:x,compact:S,minimal:`py-4 gap-3`,table:C},t[2]=x,t[3]=S,t[4]=C,t[5]=w):w=t[5];let se=w,T;t[6]===Symbol.for(`react.memo_cache_sentinel`)?(T={default:`gap-3`,compact:`gap-2`,minimal:`gap-1.5`,table:`gap-2`},t[6]=T):T=t[6];let E=T,D=v?`text-sm font-semibold`:`text-base font-semibold`,O=v?`text-xs font-medium`:`text-sm font-medium`,k;t[7]!==D||t[8]!==O?(k={default:D,compact:`text-sm font-medium`,minimal:`text-xs font-medium`,table:O},t[7]=D,t[8]=O,t[9]=k):k=t[9];let A=k,j=v?`text-xs`:`text-sm`,M;t[10]===j?M=t[11]:(M={default:j,compact:`text-xs`,minimal:`text-xs`,table:`text-xs`},t[10]=j,t[11]=M);let ce=M,N=v?`size-5`:`size-6`,P=v?`size-4`:`size-5`,F;t[12]!==N||t[13]!==P?(F={default:N,compact:`size-5`,minimal:`size-4`,table:P},t[12]=N,t[13]=P,t[14]=F):F=t[14];let I=F,L=v?`xs`:`sm`,R=v?`xs`:`sm`,z=v?`xs`:`sm`,B;t[15]!==L||t[16]!==R||t[17]!==z?(B={default:L,compact:R,minimal:`xs`,table:z},t[15]=L,t[16]=R,t[17]=z,t[18]=B):B=t[18];let V=B,H=`${se[b]} ${oe||``}`,U;t[19]===Symbol.for(`react.memo_cache_sentinel`)?(U=(0,o.jsx)(`style`,{jsx:!0,children:`
        @keyframes slideInFade {
          from {
            opacity: 0;
            transform: translateY(12px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }

        @keyframes float {
          0%,
          100% {
            transform: translateY(0px);
          }
          50% {
            transform: translateY(-8px);
          }
        }

        [data-slot='empty'] {
          animation: slideInFade 0.5s ease-out;
        }

        [data-slot='empty-icon'] {
          animation: float 3s ease-in-out infinite;
        }

        @media (max-width: 767px) {
          [data-slot='empty-icon'] {
            animation: none;
          }
        }
      `}),t[19]=U):U=t[19];let W;t[20]!==s||t[21]!==b||t[22]!==I?(W=s&&(0,o.jsx)(te,{variant:`icon`,children:(0,o.jsx)(`div`,{className:`text-secondary`,children:(0,o.jsx)(s,{className:`${I[b]} transition-all`,strokeWidth:1.5})})}),t[20]=s,t[21]=b,t[22]=I,t[23]=W):W=t[23];let G=E[b],K=A[b],q;t[24]!==K||t[25]!==p?(q=(0,o.jsx)(a,{className:K,children:p}),t[24]=K,t[25]=p,t[26]=q):q=t[26];let J=ce[b],Y;t[27]!==m||t[28]!==J?(Y=(0,o.jsx)(re,{className:J,children:m}),t[27]=m,t[28]=J,t[29]=Y):Y=t[29];let X;t[30]!==G||t[31]!==q||t[32]!==Y?(X=(0,o.jsxs)(ne,{className:G,children:[q,Y]}),t[30]=G,t[31]=q,t[32]=Y,t[33]=X):X=t[33];let Z;t[34]!==W||t[35]!==X?(Z=(0,o.jsxs)(i,{children:[W,X]}),t[34]=W,t[35]=X,t[36]=Z):Z=t[36];let Q;t[37]!==u||t[38]!==V||t[39]!==b||t[40]!==y||t[41]!==g?(Q=(y||g.length>0)&&(0,o.jsxs)(`div`,{className:`flex flex-col items-center gap-3 ${b===`minimal`?`gap-2`:``}`,style:{animation:`slideInFade 0.5s ease-out 0.2s both`},children:[y&&(u.href?(0,o.jsx)(n,{asChild:!0,variant:u.variant||`default`,size:V[b],className:`w-full max-w-xs md:w-auto`,children:(0,o.jsx)(r,{href:u.href,children:u.label})}):(0,o.jsx)(n,{onClick:u.onClick,variant:u.variant||`default`,size:V[b],className:`w-full max-w-xs md:w-auto`,children:u.label})),g.length>0&&(0,o.jsx)(`div`,{className:`flex max-w-xs flex-wrap items-center justify-center gap-2 md:max-w-none`,children:g.map((e,t)=>(0,o.jsx)(n,{asChild:!0,variant:`ghost`,size:V[b],className:`text-center text-muted-foreground hover:text-foreground`,children:(0,o.jsx)(r,{href:e.href,children:e.label})},t))})]}),t[37]=u,t[38]=V,t[39]=b,t[40]=y,t[41]=g,t[42]=Q):Q=t[42];let $;return t[43]!==H||t[44]!==Z||t[45]!==Q?($=(0,o.jsxs)(ie,{className:H,children:[U,Z,Q]}),t[43]=H,t[44]=Z,t[45]=Q,t[46]=$):$=t[46],$}export{s as t};