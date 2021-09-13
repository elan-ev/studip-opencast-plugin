const { ApolloServer, gql } = require('apollo-server');

const schema = gql(`
  type Query {
    events: [Event]
  }

  type Event {
    id: ID!
    title: String!
    lecturer: String!
  }
`);

const events = [
  {
    id: '123-a',
    title: 'Grundlagen zu Quantenstrudel',
    lecturer: 'Prof. Dr. Proton'
  },
  {
    id: '123-b',
    title: 'Quantenstrudel: Wie sie dein Wasser reinigen',
    lecturer: 'Prof. Dr. Proton'
  },
  {
    id: '123-c',
    title: 'Quantenstrudel: Technische Umsetzung',
    lecturer: 'Prof. Dr. Proton'
  }
]

var resolvers = {
  Query: {
    events: () => events
  },
};

const server = new ApolloServer({ 
  typeDefs: schema, 
  resolvers: resolvers
});

server.listen(4001).then(({ url }) => {
  console.log('API server running at localhost:4001');
});
